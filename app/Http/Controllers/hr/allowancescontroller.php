<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\hr\HrAllowance;
use App\Models\employee\employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class allowancescontroller extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr_allowances_view')->only(['index', 'show', 'employeesByBranch']);
        $this->middleware('permission:hr_allowances_create')->only(['store']);
        $this->middleware('permission:hr_allowances_edit')->only(['update']);
        $this->middleware('permission:hr_allowances_delete')->only(['destroy']);
        $this->middleware('permission:hr_allowances_approve')->only(['approve']);
    }

    // ─────────────────────────────────────────
    // Helpers — Branch Access
    // ─────────────────────────────────────────

    private function accessibleBranchIds(): array
    {
        $user = Auth::user();
        if (!$user->employee_id) return [];

        return DB::table('employee_branch')
            ->where('employee_id', $user->employee_id)
            ->pluck('branch_id')
            ->map(fn($id) => (int)$id)
            ->toArray();
    }

    private function userCanAccessBranch(int $branchId): bool
    {
        $ids = $this->accessibleBranchIds();
        return empty($ids) || in_array($branchId, $ids);
    }

    // ─────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────

    public function index(Request $request)
    {
        $branchIds = $this->accessibleBranchIds();

        // ✅ Scope يعمل تلقائياً — فروع المستخدم فقط
        $branches = Branch::where('status', 1)->orderBy('id')->get();

        // Default branch: أول فرع متاح للمستخدم
        $defaultBranch = !empty($branchIds) ? $branchIds[0] : 0;
        $branchId      = (int)($request->get('branch_id', $defaultBranch));
        $employeeId    = (int)($request->get('employee_id', 0));
        $statusFilter  = (string)$request->get('status', '');
        $typeFilter    = (string)$request->get('type', '');
        $monthFilter   = (string)$request->get('applied_month', '');

        // ✅ التحقق أن الفرع المطلوب ضمن فروع المستخدم
        if ($branchId > 0 && !$this->userCanAccessBranch($branchId)) {
            $branchId = $defaultBranch;
        }

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $q = HrAllowance::with([
            'employee',
            // ✅ withoutGlobalScope لضمان ظهور اسم الفرع دائماً
            'branch'  => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            'payroll',
        ])->orderByDesc('id');

        if ($branchId > 0) {
            $q->where('branch_id', $branchId);
            $primaryIds = $employees->pluck('id')->toArray();
            $q->whereIn('employee_id', $primaryIds);
        } else {
            $q->whereRaw('1=0');
        }

        if ($employeeId > 0)  $q->where('employee_id', $employeeId);
        if ($statusFilter !== '') $q->where('status', $statusFilter);
        if ($typeFilter !== '')   $q->where('type', $typeFilter);

        if ($monthFilter) {
            try {
                $m = Carbon::createFromFormat('Y-m', $monthFilter)->startOfMonth()->toDateString();
                $q->whereDate('applied_month', $m);
            } catch (\Throwable $e) {}
        }

        $rows  = $q->get();
        $types = HrAllowance::types();

        return view('hr.allowances.index', compact(
            'branches', 'branchId', 'employees', 'employeeId',
            'statusFilter', 'typeFilter', 'monthFilter', 'rows', 'types'
        ));
    }

    // ─────────────────────────────────────────
    // AJAX: Employees by Branch
    // ─────────────────────────────────────────

    public function employeesByBranch(Request $request)
    {
        $branchId = (int)$request->get('branch_id', 0);

        // ✅ منع جلب موظفي فرع لا يملكه المستخدم
        if ($branchId > 0 && !$this->userCanAccessBranch($branchId)) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $data = $employees->map(fn($e) => [
            'id'   => $e->id,
            'name' => $e->full_name ?? $e->getFullNameAttribute(),
            'code' => $e->code,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function create() { return redirect()->route('allowances.index'); }
    public function edit($id) { return redirect()->route('allowances.index'); }

    // ─────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────

    public function show($id)
    {
        $a = HrAllowance::with([
            'employee',
            'branch'  => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            'payroll',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->dto($a)]);
    }

    // ─────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'employee_id'   => 'required|exists:employees,id',
            'type'          => 'required|in:bonus,incentive,transportation,housing,meal,other',
            'reason'        => 'required|string|max:255',
            'amount'        => 'required|numeric|min:0.01|max:999999999',
            'date'          => 'required|date',
            'applied_month' => 'nullable|date_format:Y-m',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $branchId   = (int)$data['branch_id'];
        $employeeId = (int)$data['employee_id'];

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if (!$this->userCanAccessBranch($branchId)) {
            return response()->json(['success' => false, 'message' => trans('accounting.branch_not_allowed')], 403);
        }

        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        try {
            $date         = Carbon::parse($data['date'])->toDateString();
            $appliedMonth = $data['applied_month']
                ? Carbon::createFromFormat('Y-m', $data['applied_month'])->startOfMonth()->toDateString()
                : Carbon::parse($date)->startOfMonth()->toDateString();

            $a = new HrAllowance();
            $a->employee_id   = $employeeId;
            $a->branch_id     = $branchId;
            $a->type          = $data['type'] ?: 'bonus';
            $a->reason        = $data['reason'];
            $a->amount        = round((float)$data['amount'], 2);
            $a->date          = $date;
            $a->applied_month = $appliedMonth;
            $a->status        = 'pending';
            $a->payroll_id    = null;
            $a->notes         = $data['notes'] ?? null;
            $a->user_add      = Auth::id();
            $a->save();

            $a->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
                'payroll',
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('hr.allowance_saved_success'),
                'data'    => $this->dto($a),
            ]);
        } catch (\Throwable $e) {
            Log::error('allowances.store error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => config('app.debug') ? $e->getMessage() : trans('hr.error_occurred')], 500);
        }
    }

    // ─────────────────────────────────────────
    // Update
    // ─────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $a = HrAllowance::findOrFail($id);

        if ($a->status === 'applied' || !is_null($a->payroll_id)) {
            return response()->json(['success' => false, 'message' => trans('hr.record_applied_locked')], 422);
        }

        $data = $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'employee_id'   => 'required|exists:employees,id',
            'type'          => 'required|in:bonus,incentive,transportation,housing,meal,other',
            'reason'        => 'required|string|max:255',
            'amount'        => 'required|numeric|min:0.01|max:999999999',
            'date'          => 'required|date',
            'applied_month' => 'nullable|date_format:Y-m',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $branchId   = (int)$data['branch_id'];
        $employeeId = (int)$data['employee_id'];

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if (!$this->userCanAccessBranch($branchId)) {
            return response()->json(['success' => false, 'message' => trans('accounting.branch_not_allowed')], 403);
        }

        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        try {
            $date         = Carbon::parse($data['date'])->toDateString();
            $appliedMonth = $data['applied_month']
                ? Carbon::createFromFormat('Y-m', $data['applied_month'])->startOfMonth()->toDateString()
                : Carbon::parse($date)->startOfMonth()->toDateString();

            $a->employee_id   = $employeeId;
            $a->branch_id     = $branchId;
            $a->type          = $data['type'];
            $a->reason        = $data['reason'];
            $a->amount        = round((float)$data['amount'], 2);
            $a->date          = $date;
            $a->applied_month = $appliedMonth;
            $a->notes         = $data['notes'] ?? null;

            if (!in_array($a->status, ['pending', 'approved'], true)) $a->status = 'pending';

            $a->save();

            $a->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
                'payroll',
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('hr.allowance_updated_success'),
                'data'    => $this->dto($a),
            ]);
        } catch (\Throwable $e) {
            Log::error('allowances.update error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => config('app.debug') ? $e->getMessage() : trans('hr.error_occurred')], 500);
        }
    }

    // ─────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────

    public function destroy($id)
    {
        $a = HrAllowance::findOrFail($id);

        if ($a->status === 'applied' || !is_null($a->payroll_id)) {
            return response()->json(['success' => false, 'message' => trans('hr.record_applied_locked')], 422);
        }

        try {
            $a->delete();

            return response()->json([
                'success' => true,
                'message' => trans('hr.allowance_deleted_success'),
                'data'    => ['id' => $id],
            ]);
        } catch (\Throwable $e) {
            Log::error('allowances.destroy error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => config('app.debug') ? $e->getMessage() : trans('hr.error_occurred')], 500);
        }
    }

    // ─────────────────────────────────────────
    // Approve
    // ─────────────────────────────────────────

    public function approve($id)
    {
        $a = HrAllowance::findOrFail($id);

        if ($a->status !== 'pending') {
            return response()->json(['success' => false, 'message' => trans('hr.cannot_approve')], 422);
        }

        if (!is_null($a->payroll_id)) {
            return response()->json(['success' => false, 'message' => trans('hr.record_applied_locked')], 422);
        }

        try {
            $a->status = 'approved';
            $a->save();

            $a->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
                'payroll',
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('hr.allowance_approved_success'),
                'data'    => $this->dto($a),
            ]);
        } catch (\Throwable $e) {
            Log::error('allowances.approve error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => config('app.debug') ? $e->getMessage() : trans('hr.error_occurred')], 500);
        }
    }

    // ─────────────────────────────────────────
    // DTO + Private Helpers
    // ─────────────────────────────────────────

    private function dto(HrAllowance $a): array
    {
        return [
            'id'            => $a->id,
            'employee_id'   => $a->employee_id,
            'employee_name' => $a->employee?->full_name ?? ($a->employee?->getFullNameAttribute() ?? ''),
            'employee_code' => $a->employee?->code ?? '',
            'branch_id'     => $a->branch_id,
            'branch_name'   => $a->branch?->name ?? '',
            'type'          => (string)$a->type,
            'reason'        => (string)$a->reason,
            'amount'        => number_format((float)$a->amount, 2, '.', ''),
            'date'          => $a->date          ? Carbon::parse($a->date)->toDateString()          : null,
            'applied_month' => $a->applied_month ? Carbon::parse($a->applied_month)->format('Y-m')  : null,
            'status'        => (string)$a->status,
            'payroll_id'    => $a->payroll_id,
            'notes'         => $a->notes ?? '',
            'created_at'    => $a->created_at ? $a->created_at->toDateTimeString() : null,
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
