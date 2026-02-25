<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\hr\HrOvertime;
use App\Models\employee\employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class overtimecontroller extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('status', 1)->orderBy('id')->get();

        $branchId     = (int)($request->get('branch_id', Auth::user()->branch_id ?? 0));
        $employeeId   = (int)($request->get('employee_id', 0));
        $statusFilter = (string)$request->get('status', '');
        $monthFilter  = (string)$request->get('applied_month', ''); // Y-m

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $q = HrOvertime::with(['employee', 'branch', 'payroll'])
            ->orderByDesc('id');

        if ($branchId > 0) {
            $q->where('branch_id', $branchId);

            $primaryIds = $employees->pluck('id')->toArray();
            $q->whereIn('employee_id', $primaryIds);
        } else {
            $q->whereRaw('1=0');
        }

        if ($employeeId > 0) $q->where('employee_id', $employeeId);

        if ($statusFilter !== '') $q->where('status', $statusFilter);

        if ($monthFilter) {
            try {
                $m = Carbon::createFromFormat('Y-m', $monthFilter)->startOfMonth()->toDateString();
                $q->whereDate('applied_month', $m);
            } catch (\Throwable $e) {
                // ignore invalid
            }
        }

        $rows = $q->get();

        return view('hr.overtime.index', compact(
            'branches',
            'branchId',
            'employees',
            'employeeId',
            'statusFilter',
            'monthFilter',
            'rows'
        ));
    }

    // Ajax: employees by branch (primary only) + base_salary + default_hour_rate
    public function employeesByBranch(Request $request)
    {
        $branchId = (int)$request->get('branch_id', 0);
        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $data = $employees->map(function ($e) {
            $base = (float)($e->base_salary ?? 0);
            return [
                'id' => $e->id,
                'name' => $e->full_name ?? $e->getFullNameAttribute(),
                'code' => $e->code,
                'base_salary' => round($base, 2),
                'hour_rate' => HrOvertime::calcHourRate($base),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function create()
    {
        return redirect()->route('overtime.index');
    }

    public function edit($id)
    {
        return redirect()->route('overtime.index');
    }

    public function show($id)
    {
        $o = HrOvertime::with(['employee', 'branch', 'payroll'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->dto($o),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'employee_id'   => 'required|exists:employees,id',
            'date'          => 'required|date',
            'hours'         => 'required|numeric|min:0.01|max:24',
            'hour_rate'     => 'nullable|numeric|min:0.01|max:999999999',
            'applied_month' => 'nullable|date_format:Y-m',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $branchId   = (int)$data['branch_id'];
        $employeeId = (int)$data['employee_id'];

        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        try {
            $emp = employee::findOrFail($employeeId);

            $date = Carbon::parse($data['date'])->toDateString();
            $appliedMonth = $data['applied_month']
                ? Carbon::createFromFormat('Y-m', $data['applied_month'])->startOfMonth()->toDateString()
                : Carbon::parse($date)->startOfMonth()->toDateString();

            $hours = round((float)$data['hours'], 2);

            $defaultRate = HrOvertime::calcHourRate((float)($emp->base_salary ?? 0));
            $rate = isset($data['hour_rate']) && $data['hour_rate'] !== null && $data['hour_rate'] !== ''
                ? round((float)$data['hour_rate'], 2)
                : $defaultRate;

            $total = round($hours * $rate, 2);

            $o = new HrOvertime();
            $o->employee_id   = $employeeId;
            $o->branch_id     = $branchId;
            $o->date          = $date;
            $o->hours         = $hours;
            $o->hour_rate     = $rate;
            $o->total_amount  = $total;
            $o->applied_month = $appliedMonth;
            $o->status        = 'pending';
            $o->payroll_id    = null;
            $o->notes         = $data['notes'] ?? null;
            $o->user_add      = Auth::id();

            $o->save();
            $o->load(['employee', 'branch', 'payroll']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.overtime_saved_success'),
                'data'    => $this->dto($o),
            ]);
        } catch (\Throwable $e) {
            Log::error('overtime.store error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $o = HrOvertime::findOrFail($id);

        if ($o->status === 'applied' || !is_null($o->payroll_id)) {
            return response()->json(['success' => false, 'message' => trans('hr.record_applied_locked')], 422);
        }

        $data = $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'employee_id'   => 'required|exists:employees,id',
            'date'          => 'required|date',
            'hours'         => 'required|numeric|min:0.01|max:24',
            'hour_rate'     => 'nullable|numeric|min:0.01|max:999999999',
            'applied_month' => 'nullable|date_format:Y-m',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $branchId   = (int)$data['branch_id'];
        $employeeId = (int)$data['employee_id'];

        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        try {
            $emp = employee::findOrFail($employeeId);

            $date = Carbon::parse($data['date'])->toDateString();
            $appliedMonth = $data['applied_month']
                ? Carbon::createFromFormat('Y-m', $data['applied_month'])->startOfMonth()->toDateString()
                : Carbon::parse($date)->startOfMonth()->toDateString();

            $hours = round((float)$data['hours'], 2);

            $defaultRate = HrOvertime::calcHourRate((float)($emp->base_salary ?? 0));
            $rate = isset($data['hour_rate']) && $data['hour_rate'] !== null && $data['hour_rate'] !== ''
                ? round((float)$data['hour_rate'], 2)
                : $defaultRate;

            $total = round($hours * $rate, 2);

            $o->employee_id   = $employeeId;
            $o->branch_id     = $branchId;
            $o->date          = $date;
            $o->hours         = $hours;
            $o->hour_rate     = $rate;
            $o->total_amount  = $total;
            $o->applied_month = $appliedMonth;
            $o->notes         = $data['notes'] ?? null;

            if (!in_array($o->status, ['pending', 'approved'], true)) $o->status = 'pending';

            $o->save();
            $o->load(['employee', 'branch', 'payroll']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.overtime_updated_success'),
                'data'    => $this->dto($o),
            ]);
        } catch (\Throwable $e) {
            Log::error('overtime.update error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function destroy($id)
    {
        $o = HrOvertime::findOrFail($id);

        if ($o->status === 'applied' || !is_null($o->payroll_id)) {
            return response()->json(['success' => false, 'message' => trans('hr.record_applied_locked')], 422);
        }

        try {
            $o->delete();

            return response()->json([
                'success' => true,
                'message' => trans('hr.overtime_deleted_success'),
                'data'    => ['id' => $id],
            ]);
        } catch (\Throwable $e) {
            Log::error('overtime.destroy error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    // Workflow: approve (pending -> approved)
    public function approve($id)
    {
        $o = HrOvertime::findOrFail($id);

        if ($o->status !== 'pending') {
            return response()->json(['success' => false, 'message' => trans('hr.cannot_approve')], 422);
        }

        if (!is_null($o->payroll_id)) {
            return response()->json(['success' => false, 'message' => trans('hr.record_applied_locked')], 422);
        }

        try {
            $o->status = 'approved';
            $o->save();

            $o->load(['employee', 'branch', 'payroll']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.overtime_approved_success'),
                'data'    => $this->dto($o),
            ]);
        } catch (\Throwable $e) {
            Log::error('overtime.approve error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    // ─────────────────────────────────────────

    private function dto(HrOvertime $o): array
    {
        return [
            'id' => $o->id,

            'employee_id'   => $o->employee_id,
            'employee_name' => $o->employee?->full_name ?? ($o->employee?->getFullNameAttribute() ?? ''),
            'employee_code' => $o->employee?->code ?? '',

            'branch_id'   => $o->branch_id,
            'branch_name' => $o->branch?->name ?? '',

            'date' => $o->date ? Carbon::parse($o->date)->toDateString() : null,
            'applied_month' => $o->applied_month ? Carbon::parse($o->applied_month)->format('Y-m') : null,

            'hours'        => number_format((float)$o->hours, 2, '.', ''),
            'hour_rate'    => number_format((float)$o->hour_rate, 2, '.', ''),
            'total_amount' => number_format((float)$o->total_amount, 2, '.', ''),

            'status'    => (string)$o->status,
            'payroll_id'=> $o->payroll_id,

            'notes' => $o->notes ?? '',

            'created_at' => $o->created_at ? $o->created_at->toDateTimeString() : null,
        ];
    }

    private function getEmployeesByPrimaryBranch(int $branchId)
    {
        if ($branchId <= 0) return collect([]);

        return employee::whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId)
                  ->where('employee_branch.is_primary', 1);
            })
            ->where('status', 1)
            ->orderBy('id')
            ->get();
    }

    private function isEmployeePrimaryInBranch(int $employeeId, int $branchId): bool
    {
        return DB::table('employee_branch')
            ->where('employee_id', $employeeId)
            ->where('branch_id', $branchId)
            ->where('is_primary', 1)
            ->exists();
    }
}
