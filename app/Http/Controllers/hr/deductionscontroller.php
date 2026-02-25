<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\hr\HrDeduction;
use App\Models\employee\employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class deductionscontroller extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('status', 1)->orderBy('id')->get();

        $branchId     = (int)($request->get('branch_id', Auth::user()->branch_id ?? 0));
        $employeeId   = (int)($request->get('employee_id', 0));
        $statusFilter = (string)$request->get('status', '');
        $monthFilter  = (string)$request->get('applied_month', ''); // Y-m

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $q = HrDeduction::with(['employee', 'branch', 'payroll'])
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
                $m = Carbon::createFromFormat('Y-m', $monthFilter)->startOfMonth()->toDateString(); // Y-m-01
                $q->whereDate('applied_month', $m);
            } catch (\Throwable $e) {
                // ignore invalid
            }
        }

        $rows = $q->get();

        return view('hr.deductions.index', compact(
            'branches',
            'branchId',
            'employees',
            'employeeId',
            'statusFilter',
            'monthFilter',
            'rows'
        ));
    }

    // Ajax: employees by branch (primary only)
    public function employeesByBranch(Request $request)
    {
        $branchId = (int)$request->get('branch_id', 0);
        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $data = $employees->map(function ($e) {
            return [
                'id'   => $e->id,
                'name' => $e->full_name ?? $e->getFullNameAttribute(),
                'code' => $e->code,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function create()
    {
        return redirect()->route('deductions.index');
    }

    public function edit($id)
    {
        return redirect()->route('deductions.index');
    }

    public function show($id)
    {
        $d = HrDeduction::with(['employee', 'branch', 'payroll'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->dto($d),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'employee_id'   => 'required|exists:employees,id',
            'type'          => 'required|in:deduction,penalty',
            'reason'        => 'required|string|max:255',
            'amount'        => 'required|numeric|min:0.01|max:999999999',
            'date'          => 'required|date',
            'applied_month' => 'nullable|date_format:Y-m',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $branchId   = (int)$data['branch_id'];
        $employeeId = (int)$data['employee_id'];

        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        try {
            $date = Carbon::parse($data['date'])->toDateString();
            $appliedMonth = $data['applied_month']
                ? Carbon::createFromFormat('Y-m', $data['applied_month'])->startOfMonth()->toDateString()
                : Carbon::parse($date)->startOfMonth()->toDateString();

            $d = new HrDeduction();
            $d->employee_id   = $employeeId;
            $d->branch_id     = $branchId;
            $d->type          = $data['type'];
            $d->reason        = $data['reason'];
            $d->amount        = round((float)$data['amount'], 2);
            $d->date          = $date;
            $d->applied_month = $appliedMonth;
            $d->status        = 'pending';
            $d->payroll_id    = null;
            $d->notes         = $data['notes'] ?? null;
            $d->user_add      = Auth::id();

            $d->save();
            $d->load(['employee', 'branch', 'payroll']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.deduction_saved_success'),
                'data'    => $this->dto($d),
            ]);
        } catch (\Throwable $e) {
            Log::error('deductions.store error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $d = HrDeduction::with(['employee', 'branch', 'payroll'])->findOrFail($id);

        if ($d->status === 'applied' || !is_null($d->payroll_id)) {
            return response()->json(['success' => false, 'message' => trans('hr.deduction_applied_locked')], 422);
        }

        $data = $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'employee_id'   => 'required|exists:employees,id',
            'type'          => 'required|in:deduction,penalty',
            'reason'        => 'required|string|max:255',
            'amount'        => 'required|numeric|min:0.01|max:999999999',
            'date'          => 'required|date',
            'applied_month' => 'nullable|date_format:Y-m',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $branchId   = (int)$data['branch_id'];
        $employeeId = (int)$data['employee_id'];

        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        try {
            $date = Carbon::parse($data['date'])->toDateString();
            $appliedMonth = $data['applied_month']
                ? Carbon::createFromFormat('Y-m', $data['applied_month'])->startOfMonth()->toDateString()
                : Carbon::parse($date)->startOfMonth()->toDateString();

            $d->employee_id   = $employeeId;
            $d->branch_id     = $branchId;
            $d->type          = $data['type'];
            $d->reason        = $data['reason'];
            $d->amount        = round((float)$data['amount'], 2);
            $d->date          = $date;
            $d->applied_month = $appliedMonth;
            $d->notes         = $data['notes'] ?? null;

            // status يبقى كما هو (pending/approved) ولا نسمح بتطبيقه يدويًا
            if (!in_array($d->status, ['pending', 'approved'], true)) $d->status = 'pending';

            $d->save();
            $d->load(['employee', 'branch', 'payroll']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.deduction_updated_success'),
                'data'    => $this->dto($d),
            ]);
        } catch (\Throwable $e) {
            Log::error('deductions.update error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function destroy($id)
    {
        $d = HrDeduction::findOrFail($id);

        if ($d->status === 'applied' || !is_null($d->payroll_id)) {
            return response()->json(['success' => false, 'message' => trans('hr.deduction_applied_locked')], 422);
        }

        try {
            $d->delete();

            return response()->json([
                'success' => true,
                'message' => trans('hr.deduction_deleted_success'),
                'data'    => ['id' => $id],
            ]);
        } catch (\Throwable $e) {
            Log::error('deductions.destroy error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    // Workflow: approve (pending -> approved)
    public function approve($id)
    {
        $d = HrDeduction::findOrFail($id);

        if ($d->status !== 'pending') {
            return response()->json(['success' => false, 'message' => trans('hr.deduction_cannot_approve')], 422);
        }

        if (!is_null($d->payroll_id)) {
            return response()->json(['success' => false, 'message' => trans('hr.deduction_applied_locked')], 422);
        }

        try {
            $d->status = 'approved';
            $d->save();

            $d->load(['employee', 'branch', 'payroll']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.deduction_approved_success'),
                'data'    => $this->dto($d),
            ]);
        } catch (\Throwable $e) {
            Log::error('deductions.approve error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    // ─────────────────────────────────────────

    private function dto(HrDeduction $d): array
    {
        return [
            'id' => $d->id,

            'employee_id'   => $d->employee_id,
            'employee_name' => $d->employee?->full_name ?? ($d->employee?->getFullNameAttribute() ?? ''),
            'employee_code' => $d->employee?->code ?? '',

            'branch_id'   => $d->branch_id,
            'branch_name' => $d->branch?->name ?? '',

            'type'   => (string)$d->type,
            'reason' => (string)$d->reason,
            'amount' => number_format((float)$d->amount, 2, '.', ''),

            'date'          => $d->date ? Carbon::parse($d->date)->toDateString() : null,
            'applied_month' => $d->applied_month ? Carbon::parse($d->applied_month)->format('Y-m') : null,

            'status'    => (string)$d->status,
            'payroll_id'=> $d->payroll_id,

            'notes' => $d->notes ?? '',

            'created_at' => $d->created_at ? $d->created_at->toDateTimeString() : null,
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
