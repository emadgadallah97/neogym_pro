<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\hr\HrAdvance;
use App\Models\hr\HrAdvanceInstallment;
use App\Models\employee\employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class advancescontroller extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('status', 1)->orderBy('id')->get();

        $branchId   = (int)($request->get('branch_id', Auth::user()->branch_id ?? 0));
        $employeeId = (int)($request->get('employee_id', 0));

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $q = HrAdvance::with(['employee', 'branch'])
            ->orderByDesc('id');

        if ($branchId > 0) {
            $q->where('branch_id', $branchId);

            // Primary only within branch (مثل attendance)
            $primaryIds = $employees->pluck('id')->toArray();
            $q->whereIn('employee_id', $primaryIds);
        } else {
            // لو مفيش فرع مختار، نخليها فاضية لتجنب عرض بيانات غلط
            $q->whereRaw('1=0');
        }

        if ($employeeId > 0) {
            $q->where('employee_id', $employeeId);
        }

        $rows = $q->get();

        return view('hr.advances.index', compact(
            'branches',
            'branchId',
            'employees',
            'employeeId',
            'rows'
        ));
    }

    // (اختياري) ajax لملء الموظفين حسب الفرع
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

    public function show($id)
    {
        $a = HrAdvance::with(['employee', 'branch', 'installments' => function ($q) {
            $q->orderBy('month');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->advanceDto($a),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAdvance($request);

        $employeeId = (int)$data['employee_id'];
        $branchId   = (int)$data['branch_id'];

        // employee must be primary in branch
        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch') ?? 'الموظف غير تابع لهذا الفرع (Primary)'], 422);
        }

        // منع سلفة جديدة إلا بعد completed (نعتبر pending/approved تمنع)
        $hasActive = HrAdvance::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('deleted_at')
            ->exists();

        if ($hasActive) {
            return response()->json([
                'success' => false,
                'message' => trans('hr.advance_has_active') ?? 'لا يمكن إنشاء سلفة جديدة قبل إكمال السلفة الحالية',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $a = new HrAdvance();
            $a->employee_id = $employeeId;
            $a->branch_id   = $branchId;

            $a->total_amount        = $data['total_amount'];
            $a->installments_count  = $data['installments_count'];
            $a->monthly_installment = $this->calcMonthlyInstallment($a->total_amount, $a->installments_count);

            $a->paid_amount      = 0;
            $a->remaining_amount = $a->total_amount;

            $a->request_date = $data['request_date'];
            $a->start_month  = $data['start_month']; // Y-m-01

            $a->status   = 'pending';
            $a->notes    = $data['notes'] ?? null;
            $a->user_add = Auth::id();

            $a->save();

            DB::commit();

            $a->load(['employee', 'branch']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.advance_saved_success') ?? 'تم حفظ السلفة بنجاح',
                'data'    => $this->advanceDto($a, false),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.store error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred') ?? 'حدث خطأ، يرجى المحاولة مجدداً';
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $a = HrAdvance::with(['installments'])->findOrFail($id);

        if (!in_array($a->status, ['pending', 'approved'], true)) {
            return response()->json([
                'success' => false,
                'message' => trans('hr.advance_cannot_edit_status') ?? 'لا يمكن تعديل سلفة في هذه الحالة',
            ], 422);
        }

        $data = $this->validateAdvance($request, $id);

        $employeeId = (int)$data['employee_id'];
        $branchId   = (int)$data['branch_id'];

        if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch') ?? 'الموظف غير تابع لهذا الفرع (Primary)'], 422);
        }

        // منع سلفة أخرى للموظف (pending/approved) غير الحالية
        $hasActive = HrAdvance::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('id', '!=', $a->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasActive) {
            return response()->json([
                'success' => false,
                'message' => trans('hr.advance_has_active') ?? 'لا يمكن إنشاء سلفة جديدة قبل إكمال السلفة الحالية',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $a->employee_id = $employeeId;
            $a->branch_id   = $branchId;

            $newTotal       = (float)$data['total_amount'];
            $newCount       = (int)$data['installments_count'];
            $newStartMonth  = $data['start_month']; // Y-m-01
            $a->request_date = $data['request_date'];
            $a->notes        = $data['notes'] ?? null;

            if ($a->status === 'pending') {
                // لا توجد أقساط بعد، فقط تحديث الحقول
                $a->total_amount        = $newTotal;
                $a->installments_count  = $newCount;
                $a->monthly_installment = $this->calcMonthlyInstallment($a->total_amount, $a->installments_count);

                $a->paid_amount      = 0;
                $a->remaining_amount = $a->total_amount;

                $a->start_month = $newStartMonth;

                $a->save();
            } else {
                // approved: إعادة جدولة غير المدفوع فقط
                $paidQ = $a->installments()
                    ->where(function ($w) {
                        $w->where('is_paid', true)->orWhereNotNull('payroll_id');
                    });

                $paidAmount = (float)$paidQ->sum('amount');
                $paidCount  = (int)$paidQ->count();

                if ($newTotal < $paidAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => trans('hr.advance_total_less_than_paid') ?? 'لا يمكن أن يكون إجمالي السلفة أقل مما تم خصمه بالفعل',
                    ], 422);
                }

                if ($newCount < $paidCount) {
                    return response()->json([
                        'success' => false,
                        'message' => trans('hr.advance_installments_less_than_paid') ?? 'عدد الأقساط لا يمكن أن يكون أقل من عدد الأقساط المدفوعة',
                    ], 422);
                }

                $remainingAmount = round($newTotal - $paidAmount, 2);
                $remainingCount  = $newCount - $paidCount;

                // إذا لا يوجد متبقي
                if ($remainingAmount <= 0) {
                    // حذف غير المدفوع واعتبارها completed
                    $a->installments()
                        ->where(function ($w) {
                            $w->where('is_paid', false)->whereNull('payroll_id');
                        })->delete();

                    $a->total_amount        = $newTotal;
                    $a->installments_count  = $newCount;
                    $a->monthly_installment = $this->calcMonthlyInstallment($a->total_amount, $a->installments_count);

                    $a->paid_amount      = $paidAmount;
                    $a->remaining_amount = 0;

                    $a->start_month = $newStartMonth; // للاحتفاظ بالقيمة الجديدة (مرجعية)
                    $a->status = 'completed';

                    $a->save();
                } else {
                    if ($remainingCount <= 0) {
                        return response()->json([
                            'success' => false,
                            'message' => trans('hr.advance_remaining_count_invalid') ?? 'عدد الأقساط المتبقية غير صالح',
                        ], 422);
                    }

                    // base month: لو في مدفوع، ابدأ من الشهر التالي لآخر قسط مدفوع، وإلا من start_month
                    $baseMonth = Carbon::parse($newStartMonth)->startOfMonth();

                    if ($paidCount > 0) {
                        $lastPaidMonth = $paidQ->orderByDesc('month')->value('month');
                        if ($lastPaidMonth) {
                            $baseMonth = Carbon::parse($lastPaidMonth)->startOfMonth()->addMonth();
                        }
                    }

                    // احذف غير المدفوع القديم
                    $a->installments()
                        ->where(function ($w) {
                            $w->where('is_paid', false)->whereNull('payroll_id');
                        })->delete();

                    // أنشئ جدول جديد لغير المدفوع فقط
                    $this->createInstallmentsForRemaining($a, $remainingAmount, $remainingCount, $baseMonth);

                    // تحديث رأس السلفة
                    $a->total_amount        = $newTotal;
                    $a->installments_count  = $newCount;
                    $a->monthly_installment = $this->calcMonthlyInstallment($a->total_amount, $a->installments_count);

                    $a->paid_amount      = $paidAmount;
                    $a->remaining_amount = $remainingAmount;

                    $a->start_month = $newStartMonth;

                    // تظل approved طالما يوجد متبقي
                    $a->status = 'approved';

                    $a->save();
                }
            }

            DB::commit();

            $a->load(['employee', 'branch']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.advance_updated_success') ?? 'تم تحديث السلفة بنجاح',
                'data'    => $this->advanceDto($a, false),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.update error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred') ?? 'حدث خطأ، يرجى المحاولة مجدداً';
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function destroy($id)
    {
        $a = HrAdvance::with('installments')->findOrFail($id);

        try {
            // لو في أقساط مدفوعة/مرتبطة برواتب، امنع الحذف للحفاظ على السجل
            $hasPaid = $a->installments()
                ->where(function ($w) {
                    $w->where('is_paid', true)->orWhereNotNull('payroll_id');
                })->exists();

            if ($hasPaid) {
                return response()->json([
                    'success' => false,
                    'message' => trans('hr.advance_has_paid_cannot_delete') ?? 'لا يمكن حذف سلفة لديها أقساط مدفوعة/مرتبطة برواتب',
                ], 422);
            }

            DB::beginTransaction();

            // حذف الأقساط (غير SoftDelete)
            $a->installments()->delete();

            // Soft delete advance
            $a->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => trans('hr.advance_deleted_success') ?? 'تم حذف السلفة بنجاح',
                'data'    => ['id' => $id],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.destroy error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred') ?? 'حدث خطأ، يرجى المحاولة مجدداً';
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
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
                'message' => trans('hr.advance_cannot_approve') ?? 'لا يمكن اعتماد هذه السلفة',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // حساب القسط تلقائيًا
            $a->monthly_installment = $this->calcMonthlyInstallment((float)$a->total_amount, (int)$a->installments_count);

            // توليد الأقساط من start_month
            $this->createInstallmentsFull($a);

            // تحديث مبالغ
            $a->paid_amount      = 0;
            $a->remaining_amount = (float)$a->total_amount;

            $a->status = 'approved';
            $a->save();

            DB::commit();

            $a->load(['employee', 'branch']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.advance_approved_success') ?? 'تم اعتماد السلفة وتوليد الأقساط',
                'data'    => $this->advanceDto($a, false),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.approve error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred') ?? 'حدث خطأ، يرجى المحاولة مجدداً';
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $a = HrAdvance::with('installments')->findOrFail($id);

        if ($a->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => trans('hr.advance_cannot_reject') ?? 'لا يمكن رفض هذه السلفة',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // لا يفترض وجود أقساط (لأننا لا ننشئها إلا عند approved)، لكن للاحتياط
            $a->installments()->delete();

            $a->status = 'rejected';
            $a->save();

            DB::commit();

            $a->load(['employee', 'branch']);

            return response()->json([
                'success' => true,
                'message' => trans('hr.advance_rejected_success') ?? 'تم رفض السلفة',
                'data'    => $this->advanceDto($a, false),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('advances.reject error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred') ?? 'حدث خطأ، يرجى المحاولة مجدداً';
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    // ─────────────────────────────────────────
    // Validation + helpers
    // ─────────────────────────────────────────
    private function validateAdvance(Request $request, ?int $ignoreId = null): array
    {
        // start_month: input month "Y-m" -> store "Y-m-01"
        $validator = Validator::make($request->all(), [
            'branch_id'          => 'required|exists:branches,id',
            'employee_id'        => 'required|exists:employees,id',
            'total_amount'       => 'required|numeric|min:1|max:999999999',
            'installments_count' => 'required|integer|min:1|max:120',
            'request_date'       => 'required|date',
            'start_month'        => 'required|date_format:Y-m',
            'notes'              => 'nullable|string|max:1000',
        ], [
            'start_month.date_format' => trans('hr.start_month_format') ?? 'صيغة شهر بداية الخصم غير صحيحة',
        ]);

        $validator->after(function ($v) use ($request) {

            $employeeId = (int)$request->input('employee_id', 0);
            $branchId   = (int)$request->input('branch_id', 0);

            if ($employeeId && $branchId) {
                if (!$this->isEmployeePrimaryInBranch($employeeId, $branchId)) {
                    $v->errors()->add('employee_id', trans('hr.employee_not_in_branch') ?? 'الموظف غير تابع لهذا الفرع (Primary)');
                }
            }
        });

        $data = $validator->validate();

        $data['branch_id']   = (int)$data['branch_id'];
        $data['employee_id'] = (int)$data['employee_id'];

        $data['total_amount'] = round((float)$data['total_amount'], 2);

        $data['installments_count'] = (int)$data['installments_count'];

        $data['request_date'] = Carbon::parse($data['request_date'])->toDateString();

        $data['start_month'] = Carbon::createFromFormat('Y-m', $data['start_month'])->startOfMonth()->toDateString(); // Y-m-01

        return $data;
    }

    private function calcMonthlyInstallment(float $total, int $count): float
    {
        if ($count <= 0) return 0;
        return round($total / $count, 2);
    }

    private function createInstallmentsFull(HrAdvance $a): void
    {
        // احذف أي أقساط موجودة (احتياط)
        $a->installments()->delete();

        $count = (int)$a->installments_count;
        $startMonth = Carbon::parse($a->start_month)->startOfMonth();

        $baseAmount = $this->calcMonthlyInstallment((float)$a->total_amount, $count);

        $sum = 0;
        for ($i = 0; $i < $count; $i++) {
            $month = (clone $startMonth)->addMonths($i)->toDateString();

            $amount = $baseAmount;
            if ($i === ($count - 1)) {
                // ضبط آخر قسط ليتطابق مع الإجمالي
                $amount = round((float)$a->total_amount - $sum, 2);
            }

            HrAdvanceInstallment::create([
                'advance_id' => $a->id,
                'employee_id'=> $a->employee_id,
                'month'      => $month,
                'amount'     => $amount,
                'is_paid'    => false,
                'payroll_id' => null,
                'paid_date'  => null,
            ]);

            $sum = round($sum + $amount, 2);
        }
    }

    private function createInstallmentsForRemaining(HrAdvance $a, float $remainingAmount, int $remainingCount, Carbon $baseMonth): void
    {
        $base = round($remainingAmount / $remainingCount, 2);

        $sum = 0;
        for ($i = 0; $i < $remainingCount; $i++) {
            $month = (clone $baseMonth)->addMonths($i)->startOfMonth()->toDateString();

            $amount = $base;
            if ($i === ($remainingCount - 1)) {
                $amount = round($remainingAmount - $sum, 2);
            }

            HrAdvanceInstallment::create([
                'advance_id' => $a->id,
                'employee_id'=> $a->employee_id,
                'month'      => $month,
                'amount'     => $amount,
                'is_paid'    => false,
                'payroll_id' => null,
                'paid_date'  => null,
            ]);

            $sum = round($sum + $amount, 2);
        }
    }

    private function advanceDto(HrAdvance $a, bool $withInstallments = true): array
    {
        $employeeName = $a->employee?->full_name ?? ($a->employee?->getFullNameAttribute() ?? '');
        $branchName   = $a->branch?->name ?? '';

        $dto = [
            'id' => $a->id,

            'employee_id'   => $a->employee_id,
            'employee_name' => $employeeName,
            'employee_code' => $a->employee?->code ?? '',

            'branch_id'   => $a->branch_id,
            'branch_name' => $branchName,

            'total_amount'        => number_format((float)$a->total_amount, 2, '.', ''),
            'monthly_installment' => number_format((float)$a->monthly_installment, 2, '.', ''),
            'installments_count'  => (int)$a->installments_count,
            'paid_amount'         => number_format((float)($a->paid_amount ?? 0), 2, '.', ''),
            'remaining_amount'    => number_format((float)($a->remaining_amount ?? 0), 2, '.', ''),

            'request_date' => $a->request_date ? Carbon::parse($a->request_date)->toDateString() : null,
            'start_month'  => $a->start_month ? Carbon::parse($a->start_month)->format('Y-m') : null,

            'status' => (string)$a->status,
            'notes'  => $a->notes ?? '',

            'created_at' => $a->created_at ? $a->created_at->toDateTimeString() : null,
        ];

        if ($withInstallments) {
            $inst = $a->relationLoaded('installments') ? $a->installments : $a->installments()->orderBy('month')->get();

            $dto['installments'] = $inst->map(function ($i) {
                return [
                    'id'        => $i->id,
                    'month'     => $i->month ? Carbon::parse($i->month)->format('Y-m') : null,
                    'amount'    => number_format((float)$i->amount, 2, '.', ''),
                    'is_paid'   => (int)$i->is_paid,
                    'payroll_id'=> $i->payroll_id,
                    'paid_date' => $i->paid_date ? Carbon::parse($i->paid_date)->toDateString() : null,
                ];
            })->values();
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
}
