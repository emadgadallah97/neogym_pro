<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\accounting\Expense;
use App\Models\accounting\ExpensesType;
use App\Models\general\Branch;
use App\Models\hr\HrAdvance;
use App\Models\hr\HrAdvanceInstallment;
use App\Models\employee\employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class advancescontroller extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr_advances_view');
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

        // ✅ التحقق أن الفرع المطلوب ضمن فروع المستخدم
        if ($branchId > 0 && !$this->userCanAccessBranch($branchId)) {
            $branchId = $defaultBranch;
        }

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $q = HrAdvance::with([
            'employee',
            // ✅ withoutGlobalScope لضمان ظهور اسم الفرع دائماً
            'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
        ])->orderByDesc('id');

        if ($branchId > 0) {
            $q->where('branch_id', $branchId);
            $primaryIds = $employees->pluck('id')->toArray();
            $q->whereIn('employee_id', $primaryIds);
        } else {
            $q->whereRaw('1=0');
        }

        if ($employeeId > 0) {
            $q->where('employee_id', $employeeId);
        }

        $rows          = $q->get();
        $ExpensesTypes = ExpensesType::where('status', 1)->orderByDesc('id')->get();

        return view('hr.advances.index', compact(
            'branches', 'branchId', 'employees', 'employeeId', 'rows', 'ExpensesTypes'
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

    // ─────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────

    public function show($id)
    {
        $a = HrAdvance::with([
            'employee',
            // ✅ withoutGlobalScope لضمان ظهور اسم الفرع
            'branch'       => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            'installments' => fn($q) => $q->orderBy('month'),
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->advanceDto($a),
        ]);
    }

    // ─────────────────────────────────────────
    // Store
    // ─────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $this->validateAdvance($request);

        $employeeId = (int)$data['employee_id'];
        $branchId   = (int)$data['branch_id'];

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if (!$this->userCanAccessBranch($branchId)) {
            return response()->json([
                'success' => false,
                'message' => $this->t('accounting.branch_not_allowed', [], 'غير مسموح بالوصول لهذا الفرع'),
            ], 403);
        }

        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json([
                'success' => false,
                'message' => $this->t('hr.employee_not_in_branch', [], 'الموظف غير تابع لهذا الفرع (Primary)'),
            ], 422);
        }

        $hasActive = HrAdvance::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('deleted_at')
            ->exists();

        if ($hasActive) {
            return response()->json([
                'success' => false,
                'message' => $this->t('hr.advance_has_active', [], 'لا يمكن إنشاء سلفة جديدة قبل إكمال السلفة الحالية'),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $a                      = new HrAdvance();
            $a->employee_id         = $employeeId;
            $a->branch_id           = $branchId;
            $a->total_amount        = $data['total_amount'];
            $a->installments_count  = $data['installments_count'];
            $a->monthly_installment = $this->calcMonthlyInstallment($a->total_amount, $a->installments_count);
            $a->paid_amount         = 0;
            $a->remaining_amount    = $a->total_amount;
            $a->request_date        = $data['request_date'];
            $a->start_month         = $data['start_month'];
            $a->status              = 'pending';
            $a->notes               = $data['notes'] ?? null;
            $a->user_add            = Auth::id();
            $a->save();

            DB::commit();

            $a->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->t('hr.advance_saved_success', [], 'تم حفظ السلفة بنجاح'),
                'data'    => $this->advanceDto($a, false),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.store error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => $this->errorMsg($e)], 500);
        }
    }

    // ─────────────────────────────────────────
    // Update
    // ─────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $a = HrAdvance::with(['installments'])->findOrFail($id);

        if (!in_array($a->status, ['pending', 'approved'], true)) {
            return response()->json([
                'success' => false,
                'message' => $this->t('hr.advance_cannot_edit_status', [], 'لا يمكن تعديل سلفة في هذه الحالة'),
            ], 422);
        }

        $data = $this->validateAdvance($request, $id);

        $employeeId = (int)$data['employee_id'];
        $branchId   = (int)$data['branch_id'];

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if (!$this->userCanAccessBranch($branchId)) {
            return response()->json([
                'success' => false,
                'message' => $this->t('accounting.branch_not_allowed', [], 'غير مسموح بالوصول لهذا الفرع'),
            ], 403);
        }

        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json([
                'success' => false,
                'message' => $this->t('hr.employee_not_in_branch', [], 'الموظف غير تابع لهذا الفرع (Primary)'),
            ], 422);
        }

        $hasActive = HrAdvance::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('id', '!=', $a->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasActive) {
            return response()->json([
                'success' => false,
                'message' => $this->t('hr.advance_has_active', [], 'لا يمكن إنشاء سلفة جديدة قبل إكمال السلفة الحالية'),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $a->employee_id  = $employeeId;
            $a->branch_id    = $branchId;
            $a->request_date = $data['request_date'];
            $a->notes        = $data['notes'] ?? null;

            $newTotal      = (float)$data['total_amount'];
            $newCount      = (int)$data['installments_count'];
            $newStartMonth = $data['start_month'];

            if ($a->status === 'pending') {
                $a->total_amount        = $newTotal;
                $a->installments_count  = $newCount;
                $a->monthly_installment = $this->calcMonthlyInstallment($newTotal, $newCount);
                $a->paid_amount         = 0;
                $a->remaining_amount    = $newTotal;
                $a->start_month         = $newStartMonth;
                $a->save();
            } else {
                $paidQ = $a->installments()->where(function ($w) {
                    $w->where('is_paid', true)->orWhereNotNull('payroll_id');
                });

                $paidAmount = (float)$paidQ->sum('amount');
                $paidCount  = (int)$paidQ->count();

                if ($newTotal < $paidAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => $this->t('hr.advance_total_less_than_paid', [], 'لا يمكن أن يكون إجمالي السلفة أقل مما تم خصمه بالفعل'),
                    ], 422);
                }

                if ($newCount < $paidCount) {
                    return response()->json([
                        'success' => false,
                        'message' => $this->t('hr.advance_installments_less_than_paid', [], 'عدد الأقساط لا يمكن أن يكون أقل من عدد الأقساط المدفوعة'),
                    ], 422);
                }

                $remainingAmount = round($newTotal - $paidAmount, 2);
                $remainingCount  = $newCount - $paidCount;

                if ($remainingAmount <= 0) {
                    $a->installments()->where(fn($w) => $w->where('is_paid', false)->whereNull('payroll_id'))->delete();

                    $a->total_amount        = $newTotal;
                    $a->installments_count  = $newCount;
                    $a->monthly_installment = $this->calcMonthlyInstallment($newTotal, $newCount);
                    $a->paid_amount         = $paidAmount;
                    $a->remaining_amount    = 0;
                    $a->start_month         = $newStartMonth;
                    $a->status              = 'completed';
                    $a->save();
                } else {
                    if ($remainingCount <= 0) {
                        return response()->json([
                            'success' => false,
                            'message' => $this->t('hr.advance_remaining_count_invalid', [], 'عدد الأقساط المتبقية غير صالح'),
                        ], 422);
                    }

                    $baseMonth = Carbon::parse($newStartMonth)->startOfMonth();

                    if ($paidCount > 0) {
                        $lastPaidMonth = $paidQ->orderByDesc('month')->value('month');
                        if ($lastPaidMonth) {
                            $baseMonth = Carbon::parse($lastPaidMonth)->startOfMonth()->addMonth();
                        }
                    }

                    $a->installments()->where(fn($w) => $w->where('is_paid', false)->whereNull('payroll_id'))->delete();

                    $this->createInstallmentsForRemaining($a, $remainingAmount, $remainingCount, $baseMonth);

                    $a->total_amount        = $newTotal;
                    $a->installments_count  = $newCount;
                    $a->monthly_installment = $this->calcMonthlyInstallment($newTotal, $newCount);
                    $a->paid_amount         = $paidAmount;
                    $a->remaining_amount    = $remainingAmount;
                    $a->start_month         = $newStartMonth;
                    $a->status              = 'approved';
                    $a->save();
                }
            }

            DB::commit();

            $a->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->t('hr.advance_updated_success', [], 'تم تحديث السلفة بنجاح'),
                'data'    => $this->advanceDto($a, false),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.update error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => $this->errorMsg($e)], 500);
        }
    }

    // ─────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────

    public function destroy($id)
    {
        $a = HrAdvance::with('installments')->findOrFail($id);

        try {
            $hasPaid = $a->installments()->where(fn($w) => $w->where('is_paid', true)->orWhereNotNull('payroll_id'))->exists();

            if ($hasPaid) {
                return response()->json([
                    'success' => false,
                    'message' => $this->t('hr.advance_has_paid_cannot_delete', [], 'لا يمكن حذف سلفة لديها أقساط مدفوعة/مرتبطة برواتب'),
                ], 422);
            }

            DB::beginTransaction();
            $a->installments()->delete();
            $a->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $this->t('hr.advance_deleted_success', [], 'تم حذف السلفة بنجاح'),
                'data'    => ['id' => $id],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.destroy error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => $this->errorMsg($e)], 500);
        }
    }

    // ─────────────────────────────────────────
    // Workflow: approve / reject
    // ─────────────────────────────────────────

    public function approve($id)
    {
        $a = HrAdvance::with('installments')->findOrFail($id);

        if ($a->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => $this->t('hr.advance_cannot_approve', [], 'لا يمكن اعتماد هذه السلفة'),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $this->doApproveLogic($a);
            DB::commit();

            $a->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->t('hr.advance_approved_success', [], 'تم اعتماد السلفة وتوليد الأقساط'),
                'data'    => $this->advanceDto($a, false),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.approve error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => $this->errorMsg($e)], 500);
        }
    }

    public function approveWithExpense(Request $request, $id)
    {
        $a = HrAdvance::with('installments')->findOrFail($id);

        if ($a->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => $this->t('hr.advance_cannot_approve', [], 'لا يمكن اعتماد هذه السلفة'),
            ], 422);
        }

        $expenseValidator = Validator::make($request->all(), [
            'expense_type_id'      => 'required|exists:expenses_types,id',
            'expense_disbursed_by' => 'nullable|exists:employees,id',
        ]);

        if ($expenseValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $expenseValidator->errors()->first(),
                'errors'  => $expenseValidator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $this->doApproveLogic($a);

            $expenseTypeId = (int)$request->input('expense_type_id');
            $disbursedById = $request->input('expense_disbursed_by')
                ? (int)$request->input('expense_disbursed_by')
                : null;

            $empName = $a->employee
                ? ($a->employee->full_name ?? trim(($a->employee->first_name ?? '') . ' ' . ($a->employee->last_name ?? '')))
                : ('#' . $a->employee_id);

            $description = $this->t('hr.advance_expense_description', ['name' => $empName], 'سلفة موظف - ' . $empName);

            Expense::create([
                'branchid'              => $a->branch_id,
                'expensestypeid'        => $expenseTypeId,
                'expensedate'           => now()->toDateString(),
                'amount'                => $a->total_amount,
                'recipientname'         => $empName,
                'recipientphone'        => null,
                'recipientnationalid'   => null,
                'disbursedbyemployeeid' => $disbursedById,
                'description'           => $description,
                'notes'                 => $a->notes,
                'iscancelled'           => false,
                'cancelledat'           => null,
                'cancelledby'           => null,
                'useradd'               => Auth::id(),
                'userupdate'            => null,
                'hr_advance_id'         => $a->id,
            ]);

            DB::commit();

            $a->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->t('hr.advance_approved_with_expense_success', [], 'تم اعتماد السلفة وصرفها وتسجيل المصروف بنجاح'),
                'data'    => $this->advanceDto($a, false),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.approveWithExpense error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => $this->errorMsg($e)], 500);
        }
    }

    private function doApproveLogic(HrAdvance $a): void
    {
        $a->monthly_installment = $this->calcMonthlyInstallment((float)$a->total_amount, (int)$a->installments_count);
        $this->createInstallmentsFull($a);
        $a->paid_amount      = 0;
        $a->remaining_amount = (float)$a->total_amount;
        $a->status           = 'approved';
        $a->save();
    }

    public function reject(Request $request, $id)
    {
        $a = HrAdvance::with('installments')->findOrFail($id);

        if ($a->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => $this->t('hr.advance_cannot_reject', [], 'لا يمكن رفض هذه السلفة'),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $a->installments()->delete();
            $a->status = 'rejected';
            $a->save();
            DB::commit();

            $a->load([
                'employee',
                'branch' => fn($q) => $q->withoutGlobalScope(\App\Models\Scopes\BranchAccessScope::class),
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->t('hr.advance_rejected_success', [], 'تم رفض السلفة'),
                'data'    => $this->advanceDto($a, false),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.reject error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => $this->errorMsg($e)], 500);
        }
    }

    // ─────────────────────────────────────────
    // Validation + Helpers
    // ─────────────────────────────────────────

    private function validateAdvance(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'branch_id'          => 'required|exists:branches,id',
            'employee_id'        => 'required|exists:employees,id',
            'total_amount'       => 'required|numeric|min:1|max:999999999',
            'installments_count' => 'required|integer|min:1|max:120',
            'request_date'       => 'required|date',
            'start_month'        => 'required|date_format:Y-m',
            'notes'              => 'nullable|string|max:1000',
        ], [
            'start_month.date_format' => $this->t('hr.start_month_format', [], 'صيغة شهر بداية الخصم غير صحيحة'),
        ]);

        $validator->after(function ($v) use ($request) {
            $employeeId = (int)$request->input('employee_id', 0);
            $branchId   = (int)$request->input('branch_id', 0);

            if ($employeeId && $branchId) {
                if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
                    $v->errors()->add(
                        'employee_id',
                        $this->t('hr.employee_not_in_branch', [], 'الموظف غير تابع لهذا الفرع (Primary)')
                    );
                }
            }
        });

        $data = $validator->validate();

        $data['branch_id']          = (int)$data['branch_id'];
        $data['employee_id']        = (int)$data['employee_id'];
        $data['total_amount']       = round((float)$data['total_amount'], 2);
        $data['installments_count'] = (int)$data['installments_count'];
        $data['request_date']       = Carbon::parse($data['request_date'])->toDateString();
        $data['start_month']        = Carbon::createFromFormat('Y-m', $data['start_month'])
                                        ->startOfMonth()->toDateString();

        return $data;
    }

    private function calcMonthlyInstallment(float $total, int $count): float
    {
        if ($count <= 0) return 0;
        return round($total / $count, 2);
    }

    private function createInstallmentsFull(HrAdvance $a): void
    {
        $a->installments()->delete();

        $count      = (int)$a->installments_count;
        $startMonth = Carbon::parse($a->start_month)->startOfMonth();
        $baseAmount = $this->calcMonthlyInstallment((float)$a->total_amount, $count);

        $sum = 0;
        for ($i = 0; $i < $count; $i++) {
            $month  = (clone $startMonth)->addMonths($i)->toDateString();
            $amount = ($i === $count - 1)
                ? round((float)$a->total_amount - $sum, 2)
                : $baseAmount;

            HrAdvanceInstallment::create([
                'advance_id'  => $a->id,
                'employee_id' => $a->employee_id,
                'month'       => $month,
                'amount'      => $amount,
                'is_paid'     => false,
                'payroll_id'  => null,
                'paid_date'   => null,
            ]);

            $sum = round($sum + $amount, 2);
        }
    }

    private function createInstallmentsForRemaining(HrAdvance $a, float $remainingAmount, int $remainingCount, Carbon $baseMonth): void
    {
        $base = round($remainingAmount / $remainingCount, 2);
        $sum  = 0;

        for ($i = 0; $i < $remainingCount; $i++) {
            $month  = (clone $baseMonth)->addMonths($i)->startOfMonth()->toDateString();
            $amount = ($i === $remainingCount - 1)
                ? round($remainingAmount - $sum, 2)
                : $base;

            HrAdvanceInstallment::create([
                'advance_id'  => $a->id,
                'employee_id' => $a->employee_id,
                'month'       => $month,
                'amount'      => $amount,
                'is_paid'     => false,
                'payroll_id'  => null,
                'paid_date'   => null,
            ]);

            $sum = round($sum + $amount, 2);
        }
    }

    private function advanceDto(HrAdvance $a, bool $withInstallments = true): array
    {
        $dto = [
            'id'                  => $a->id,
            'employee_id'         => $a->employee_id,
            'employee_name'       => $a->employee?->full_name ?? ($a->employee?->getFullNameAttribute() ?? ''),
            'employee_code'       => $a->employee?->code ?? '',
            'branch_id'           => $a->branch_id,
            'branch_name'         => $a->branch?->name ?? '',
            'total_amount'        => number_format((float)$a->total_amount, 2, '.', ''),
            'monthly_installment' => number_format((float)$a->monthly_installment, 2, '.', ''),
            'installments_count'  => (int)$a->installments_count,
            'paid_amount'         => number_format((float)($a->paid_amount ?? 0), 2, '.', ''),
            'remaining_amount'    => number_format((float)($a->remaining_amount ?? 0), 2, '.', ''),
            'request_date'        => $a->request_date ? Carbon::parse($a->request_date)->toDateString() : null,
            'start_month'         => $a->start_month  ? Carbon::parse($a->start_month)->format('Y-m')   : null,
            'status'              => (string)$a->status,
            'notes'               => $a->notes ?? '',
            'created_at'          => $a->created_at ? $a->created_at->toDateTimeString() : null,
        ];

        if ($withInstallments) {
            $inst = $a->relationLoaded('installments')
                ? $a->installments
                : $a->installments()->orderBy('month')->get();

            $dto['installments'] = $inst->map(fn($i) => [
                'id'         => $i->id,
                'month'      => $i->month    ? Carbon::parse($i->month)->format('Y-m')       : null,
                'amount'     => number_format((float)$i->amount, 2, '.', ''),
                'is_paid'    => (int)$i->is_paid,
                'payroll_id' => $i->payroll_id,
                'paid_date'  => $i->paid_date ? Carbon::parse($i->paid_date)->toDateString() : null,
            ])->values();
        }

        return $dto;
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

    private function t(string $key, array $replace = [], string $fallback = ''): string
    {
        if (Lang::has($key)) return trans($key, $replace);
        foreach ($replace as $k => $v) {
            $fallback = str_replace(':' . $k, $v, $fallback);
        }
        return $fallback;
    }

    private function errorMsg(\Throwable $e): string
    {
        if (config('app.debug')) return $e->getMessage();
        return $this->t('hr.error_occurred', [], 'حدث خطأ، يرجى المحاولة مجدداً');
    }
}
