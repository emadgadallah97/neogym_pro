<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\employee\employee;
use App\Models\general\Branch;
use App\Models\hr\HrAdvance;
use App\Models\hr\HrAdvanceInstallment;
use App\Models\hr\HrAllowance;
use App\Models\hr\HrAttendance;
use App\Models\hr\HrDeduction;
use App\Models\hr\HrEmployeeShift;
use App\Models\hr\HrOvertime;
use App\Models\hr\HrPayroll;
use App\Models\general\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class payrollscontroller extends Controller
{
    private const AUTO_ATTENDANCE_NOTES = 'AUTO_ATTENDANCE_DEDUCTION';
    private const WORK_DAYS = 26;

    private const AUTO_OT_TAG = 'AUTO_OT_FROM_PAYROLL';
    private const OT_MULTIPLIER = 2;
    private const DEFAULT_SHIFT_HOURS = 8;

    public function index(Request $request)
    {
        $branches = Branch::where('status', 1)->orderBy('id')->get();

        $branchId     = (int)($request->get('branch_id', Auth::user()->branch_id ?? 0));
        $employeeId   = (int)($request->get('employee_id', 0));
        $statusFilter = (string)$request->get('status', '');
        $monthFilter  = (string)$request->get('month', Carbon::now()->format('Y-m'));
        $action       = (string)$request->get('action', '');

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $q = HrPayroll::with(['employee', 'branch'])
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

        $monthDate  = null;
        $monthStart = null;
        if ($monthFilter) {
            try {
                $monthStart = Carbon::createFromFormat('Y-m', $monthFilter)->startOfMonth();
                $monthDate  = $monthStart->toDateString();
                $q->whereDate('month', $monthDate);
            } catch (\Throwable $e) {
                $monthDate  = null;
                $monthStart = null;
            }
        }

        $rows = $q->get();
        $kpis = $this->computePayrollKpis($rows);

        // ✅ Attendance summary per employee (single query, avoid N+1)
        $attendanceSummary = [];
        $workDays = self::WORK_DAYS;

        if ($branchId > 0 && $monthStart) {
            $monthEnd   = $monthStart->copy()->endOfMonth();
            $primaryIds = $employees->pluck('id')->toArray();

            if (!empty($primaryIds)) {
                $agg = HrAttendance::where('branch_id', $branchId)
                    ->whereIn('employee_id', $primaryIds)
                    ->whereDate('date', '>=', $monthStart->toDateString())
                    ->whereDate('date', '<=', $monthEnd->toDateString())
                    ->selectRaw("
                        employee_id,
                        SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) AS present_days,
                        SUM(CASE WHEN status = 'late'             THEN 1 ELSE 0 END) AS late_days,
                        SUM(CASE WHEN status IN ('halfday','half_day') THEN 1 ELSE 0 END) AS halfday_days,
                        SUM(CASE WHEN status = 'absent'           THEN 1 ELSE 0 END) AS absent_days,
                        SUM(COALESCE(total_hours, 0))                                AS total_hours
                    ")
                    ->groupBy('employee_id')
                    ->get()
                    ->keyBy('employee_id');

                foreach ($agg as $eId => $r) {
                    $attendanceSummary[(int)$eId] = [
                        'present_days' => (int)($r->present_days  ?? 0),
                        'late_days'    => (int)($r->late_days     ?? 0),
                        'halfday_days' => (int)($r->halfday_days  ?? 0),
                        'absent_days'  => (int)($r->absent_days   ?? 0),
                        'total_hours'  => round((float)($r->total_hours ?? 0), 2),
                    ];
                }
            }
        }

        if ($action === 'metrics') {
            return response()->json($kpis);
        }

        if ($action === 'print') {
            $chips = [];

            $branchName = $branchId ? ($branches->firstWhere('id', $branchId)?->name ?? '') : '';
            if ($branchName) $chips[] = (trans('hr.branch') ?? 'الفرع') . ': ' . $branchName;

            if ($monthFilter) $chips[] = (trans('hr.month') ?? 'الشهر') . ': ' . $monthFilter;

            if ($statusFilter !== '') $chips[] = (trans('hr.status') ?? 'الحالة') . ': ' . $statusFilter;

            if ($employeeId > 0) {
                $emp = $employees->firstWhere('id', $employeeId);
                $chips[] = (trans('hr.employee') ?? 'الموظف') . ': ' . ($emp?->full_name ?? '') . ' (' . ($emp?->code ?? '') . ')';
            }
            $settings = GeneralSetting::query()
                ->where('status', 1)
                ->first();

            $orgName = '-';
            if ($settings) {
                if (method_exists($settings, 'getTranslation')) {
                    $orgName = $settings->getTranslation('name', app()->getLocale())
                        ?: ($settings->getTranslation('name', 'ar') ?: $settings->getTranslation('name', 'en'));
                } else {
                    $orgName = $settings->name ?? '-';
                }
            }
            $meta = [
                'title'        => trans('hr.payrolls') ?? 'الرواتب',
                'org_name'     => $orgName,
                'generated_at' => now('Africa/Cairo')->format('Y-m-d H:i'),
                'total_count'  => $rows->count(),
            ];

            return view('hr.payrolls.print', compact('meta', 'chips', 'kpis', 'rows'));
        }

        if ($action === 'export_excel') {
            $filename = 'payrolls_' . ($branchId ?: 'branch') . '_' . ($monthFilter ?: 'month') . '.xls';
            $html     = $this->buildPayrollsExcelHtml($rows);

            return response($html, 200, [
                'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        return view('hr.payrolls.index', compact(
            'branches',
            'branchId',
            'employees',
            'employeeId',
            'statusFilter',
            'monthFilter',
            'rows',
            'kpis',
            'attendanceSummary',  // ✅ جديد
            'workDays'            // ✅ جديد
        ));
    }

    public function employeesByBranch(Request $request)
    {
        $branchId  = (int)$request->get('branch_id', 0);
        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $data = $employees->map(function ($e) {
            return [
                'id'                      => $e->id,
                'name'                    => $e->full_name ?? $e->getFullNameAttribute(),
                'code'                    => $e->code,
                'base_salary'             => round((float)($e->base_salary ?? 0), 2),
                'salary_transfer_method'  => (string)($e->salary_transfer_method ?? ''),
                'salary_transfer_details' => (string)($e->salary_transfer_details ?? ''),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'month'     => 'required|date_format:Y-m',
        ]);

        $branchId = (int)$data['branch_id'];

        $monthStart = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $monthEnd   = Carbon::createFromFormat('Y-m', $data['month'])->endOfMonth();
        $monthDate  = $monthStart->toDateString();

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        if ($employees->count() === 0) {
            return response()->json([
                'success' => true,
                'message' => trans('hr.no_data') ?? 'لا توجد بيانات',
                'data'    => ['created' => 0, 'updated' => 0, 'skipped_locked' => 0],
            ]);
        }

        $created = 0;
        $updated = 0;
        $skippedLocked = 0;

        try {
            DB::beginTransaction();

            $otStats = $this->autoGenerateOvertimeFromAttendance(
                $branchId,
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
                $employees
            );

            foreach ($employees as $emp) {

                $empId = (int)$emp->id;

                if ((int)($emp->status ?? 0) !== 1) continue;
                if ((float)($emp->base_salary ?? 0) <= 0) continue;

                // ✅ مهم: مع المحذوفات
                $payroll = HrPayroll::withTrashed()
                    ->where('employee_id', $empId)
                    ->where('branch_id', $branchId)
                    ->whereDate('month', $monthDate)
                    ->first();

                // ✅ لو كان محذوف SoftDelete رجّعه بدل insert جديد
                if ($payroll && $payroll->trashed()) {
                    $payroll->restore();
                    // اختياري: رجّعها Draft لأن ده توليد
                    $payroll->status = 'draft';
                }

                if ($payroll) {
                    if ($payroll->status !== 'draft') {
                        $skippedLocked++;
                        continue;
                    }
                }

                if (!$payroll) {
                    $payroll = new HrPayroll();
                    $payroll->employee_id = $empId;
                    $payroll->branch_id = $branchId;
                    $payroll->month = $monthDate;
                    $payroll->status = 'draft';
                    $payroll->user_add = Auth::id();
                    $payroll->base_salary = round((float)($emp->base_salary ?? 0), 2);

                    $method = $emp->salary_transfer_method ?? null;
                    $payroll->payment_method = $method ? (string)$method : null;
                    $payroll->salary_transfer_details = $emp->salary_transfer_details ?? null;
                }

                $amounts = $this->computeMonthAmounts($empId, $branchId, $monthStart);

                $attendancePreview = $this->computeAttendanceDeductionsPreview(
                    $empId,
                    $branchId,
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                    (float)$payroll->base_salary,
                    $monthStart
                );

                $payroll->overtime_amount     = (float)($amounts['overtime_amount'] ?? 0);
                $payroll->allowances_amount   = (float)($amounts['allowances_amount'] ?? 0);
                $payroll->advances_deduction  = (float)($amounts['advances_deduction'] ?? 0);

                $payroll->deductions_amount = round(
                    (float)($amounts['deductions_amount'] ?? 0) + (float)$attendancePreview,
                    2
                );

                $payroll->calculateSalary();

                $isNew = !$payroll->exists;
                $payroll->save();

                if ($isNew) $created++;
                else $updated++;
            }

            DB::commit();

            $msg = (trans('hr.payrolls_generated_success') ?? 'تم توليد الرواتب بنجاح')
                . " | Created: {$created}"
                . " | Updated: {$updated}"
                . " | Skipped locked: {$skippedLocked}"
                . " | OT Auto Created: " . (int)($otStats['created'] ?? 0)
                . " | OT Auto Skipped Exists: " . (int)($otStats['skipped_exists'] ?? 0)
                . " | NoShift: " . (int)($otStats['skipped_no_shift'] ?? 0)
                . " | NoTimes: " . (int)($otStats['skipped_no_times'] ?? 0)
                . " | Zero: " . (int)($otStats['skipped_zero'] ?? 0);

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => [
                    'created' => $created,
                    'updated' => $updated,
                    'skipped_locked' => $skippedLocked,
                    'overtime_auto' => $otStats,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('payrolls.generate error', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $msg = trans('hr.error_occurred') ?? 'حدث خطأ';
            if (config('app.debug')) $msg = $e->getMessage();

            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }


    public function cancelDrafts(Request $request)
    {
        $data = $request->validate([
            'branch_id'           => 'required|exists:branches,id',
            'month'               => 'required|date_format:Y-m',
            'delete_auto_overtime' => 'nullable|in:0,1',
        ]);

        $branchId     = (int)$data['branch_id'];
        $monthStart   = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $deleteAutoOt = (int)($data['delete_auto_overtime'] ?? 0) === 1;

        $employees  = $this->getEmployeesByPrimaryBranch($branchId);
        $primaryIds = $employees->pluck('id')->toArray();

        if (empty($primaryIds)) {
            return response()->json(['success' => true, 'message' => trans('hr.no_data') ?? 'لا يوجد بيانات']);
        }

        try {
            DB::beginTransaction();

            $draftPayrolls = HrPayroll::where('branch_id', $branchId)
                ->whereIn('employee_id', $primaryIds)
                ->whereDate('month', $monthStart->toDateString())
                ->where('status', 'draft')
                ->lockForUpdate()
                ->get();

            if ($draftPayrolls->count() === 0) {
                DB::commit();
                return response()->json([
                    'success' => false,
                    'message' => trans('hr.no_data') ?? 'لا يوجد مسودات للحذف',
                ], 422);
            }

            $deletedCount = HrPayroll::where('branch_id', $branchId)
                ->whereIn('employee_id', $primaryIds)
                ->whereDate('month', $monthStart->toDateString())
                ->where('status', 'draft')
                ->delete();

            $otDeleted = 0;
            if ($deleteAutoOt) {
                $otDeleted = HrOvertime::where('branch_id', $branchId)
                    ->whereIn('employee_id', $primaryIds)
                    ->whereYear('applied_month', $monthStart->year)
                    ->whereMonth('applied_month', $monthStart->month)
                    ->whereNull('payroll_id')
                    ->where('source', 'attendance')
                    ->where('notes', 'like', self::AUTO_OT_TAG . '%')
                    ->delete();
            }

            DB::commit();

            $msg = (trans('hr.done') ?? 'تم')
                . ' | Draft deleted: ' . (int)$deletedCount
                . ($deleteAutoOt ? (' | Auto OT deleted: ' . (int)$otDeleted) : '');

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('payrolls.cancelDrafts error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function approveMonth(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'month'     => 'required|date_format:Y-m',
        ]);

        $branchId   = (int)$data['branch_id'];
        $monthStart = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $monthEnd   = Carbon::createFromFormat('Y-m', $data['month'])->endOfMonth();

        $employees  = $this->getEmployeesByPrimaryBranch($branchId);
        $primaryIds = $employees->pluck('id')->toArray();

        if (empty($primaryIds)) {
            return response()->json(['success' => true, 'message' => trans('hr.no_data') ?? 'لا يوجد بيانات']);
        }

        try {
            DB::beginTransaction();

            $payrolls = HrPayroll::where('branch_id', $branchId)
                ->whereIn('employee_id', $primaryIds)
                ->whereDate('month', $monthStart->toDateString())
                ->where('status', 'draft')
                ->lockForUpdate()
                ->get();

            if ($payrolls->count() === 0) {
                DB::commit();
                return response()->json([
                    'success' => false,
                    'message' => trans('hr.no_data_to_approve') ?? 'لا يوجد رواتب مسودة للاعتماد',
                ], 422);
            }

            foreach ($payrolls as $p) {
                $empId = (int)$p->employee_id;

                $this->createOrReplaceMonthlyAttendanceDeductions(
                    $p,
                    $monthStart->toDateString(),
                    $monthEnd->toDateString()
                );

                $amounts = $this->computeMonthAmounts($empId, $branchId, $monthStart);

                $p->overtime_amount    = $amounts['overtime_amount'];
                $p->allowances_amount  = $amounts['allowances_amount'];
                $p->deductions_amount  = $amounts['deductions_amount'];
                $p->advances_deduction = $amounts['advances_deduction'];

                $p->calculateSalary();
                $p->status = 'approved';
                $p->save();

                HrOvertime::where('employee_id', $empId)
                    ->where('branch_id', $branchId)
                    ->whereYear('applied_month', $monthStart->year)
                    ->whereMonth('applied_month', $monthStart->month)
                    ->where(function ($q) {
                        $q->where('status', 'approved')->orWhere('status', 1)->orWhere('status', '1');
                    })
                    ->whereNull('payroll_id')
                    ->update(['status' => 'applied', 'payroll_id' => $p->id]);

                HrAllowance::where('employee_id', $empId)
                    ->where('branch_id', $branchId)
                    ->whereYear('applied_month', $monthStart->year)
                    ->whereMonth('applied_month', $monthStart->month)
                    ->where(function ($q) {
                        $q->where('status', 'approved')->orWhere('status', 1)->orWhere('status', '1');
                    })
                    ->whereNull('payroll_id')
                    ->update(['status' => 'applied', 'payroll_id' => $p->id]);

                HrDeduction::where('employee_id', $empId)
                    ->where('branch_id', $branchId)
                    ->whereYear('applied_month', $monthStart->year)
                    ->whereMonth('applied_month', $monthStart->month)
                    ->where(function ($q) {
                        $q->where('status', 'approved')->orWhere('status', 1)->orWhere('status', '1');
                    })
                    ->whereNull('payroll_id')
                    ->update(['status' => 'applied', 'payroll_id' => $p->id]);

                HrAdvanceInstallment::where('employee_id', $empId)
                    ->whereDate('month', $monthStart->toDateString())
                    ->where('is_paid', false)
                    ->whereNull('payroll_id')
                    ->update(['payroll_id' => $p->id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => trans('hr.payrolls_approved_success') ?? 'تم اعتماد الرواتب بنجاح',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('payrolls.approveMonth error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function payMonth(Request $request)
    {
        $data = $request->validate([
            'branch_id'          => 'required|exists:branches,id',
            'month'              => 'required|date_format:Y-m',
            'payment_date'       => 'required|date',
            'payment_reference'  => 'nullable|string|max:190',
        ]);

        $branchId    = (int)$data['branch_id'];
        $monthStart  = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $paymentDate = Carbon::parse($data['payment_date'])->toDateString();
        $paymentRef  = $data['payment_reference'] ?? null;

        $employees  = $this->getEmployeesByPrimaryBranch($branchId);
        $primaryIds = $employees->pluck('id')->toArray();

        if (empty($primaryIds)) {
            return response()->json(['success' => true, 'message' => trans('hr.no_data') ?? 'لا يوجد بيانات']);
        }

        try {
            DB::beginTransaction();

            $payrolls = HrPayroll::where('branch_id', $branchId)
                ->whereIn('employee_id', $primaryIds)
                ->whereDate('month', $monthStart->toDateString())
                ->where('status', 'approved')
                ->lockForUpdate()
                ->get();

            if ($payrolls->count() === 0) {
                DB::commit();
                return response()->json([
                    'success' => false,
                    'message' => trans('hr.no_data_to_pay') ?? 'لا يوجد رواتب معتمدة للصرف',
                ], 422);
            }

            $payrollIds = $payrolls->pluck('id')->toArray();

            $instAgg = HrAdvanceInstallment::select('advance_id', DB::raw('SUM(amount) as total_amount'))
                ->whereIn('payroll_id', $payrollIds)
                ->where('is_paid', false)
                ->groupBy('advance_id')
                ->get()
                ->keyBy('advance_id');

            foreach ($payrolls as $p) {
                $p->payment_date      = $paymentDate;
                $p->payment_reference = $paymentRef;
                $p->status            = 'paid';
                $p->save();
            }

            HrAdvanceInstallment::whereIn('payroll_id', $payrollIds)
                ->where('is_paid', false)
                ->update([
                    'is_paid'   => true,
                    'paid_date' => $paymentDate,
                ]);

            if ($instAgg->count() > 0) {
                $advanceIds = $instAgg->keys()->toArray();
                $advances   = HrAdvance::whereIn('id', $advanceIds)->lockForUpdate()->get();

                foreach ($advances as $adv) {
                    $add = (float)($instAgg[$adv->id]->total_amount ?? 0);
                    if ($add <= 0) continue;

                    $paid   = round((float)($adv->paid_amount      ?? 0) + $add, 2);
                    $remain = round((float)($adv->remaining_amount ?? 0) - $add, 2);

                    if ($remain < 0) $remain = 0;

                    $adv->paid_amount      = $paid;
                    $adv->remaining_amount = $remain;

                    if ($remain <= 0) $adv->status = 'completed';

                    $adv->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => trans('hr.payrolls_paid_success') ?? 'تم صرف الرواتب بنجاح',
                'data'    => [
                    'paid_rows'    => $payrolls->count(),
                    'payment_date' => $paymentDate,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('payrolls.payMonth error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage();
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function breakdown(Request $request)
    {
        $data = $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'month'       => 'required|date_format:Y-m',
        ]);

        $branchId   = (int)$data['branch_id'];
        $empId      = (int)$data['employee_id'];
        $monthStart = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth(); // ✅ للحضور

        $payroll = HrPayroll::where('branch_id', $branchId)
            ->where('employee_id', $empId)
            ->whereDate('month', $monthStart->toDateString())
            ->first();

        $payrollId = $payroll?->id;

        // ── Overtime ──
        $overtimes = HrOvertime::where('branch_id', $branchId)
            ->where('employee_id', $empId)
            ->whereYear('applied_month', $monthStart->year)
            ->whereMonth('applied_month', $monthStart->month)
            ->where(function ($q) use ($payrollId) {
                $q->where(function ($q2) {
                    $q2->whereNull('payroll_id')
                        ->where(function ($qq) {
                            $qq->where('status', 'approved')->orWhere('status', 1)->orWhere('status', '1');
                        });
                });
                if ($payrollId) {
                    $q->orWhere(function ($q3) use ($payrollId) {
                        $q3->where('payroll_id', $payrollId);
                    });
                }
            })
            ->orderBy('date')
            ->get(['id', 'date', 'source', 'hours', 'hour_rate', 'total_amount', 'notes', 'status', 'payroll_id']);

        // ── Allowances ──
        $allowances = HrAllowance::where('branch_id', $branchId)
            ->where('employee_id', $empId)
            ->whereYear('applied_month', $monthStart->year)
            ->whereMonth('applied_month', $monthStart->month)
            ->where(function ($q) use ($payrollId) {
                $q->where(function ($q2) {
                    $q2->whereNull('payroll_id')
                        ->where(function ($qq) {
                            $qq->where('status', 'approved')->orWhere('status', 1)->orWhere('status', '1');
                        });
                });
                if ($payrollId) {
                    $q->orWhere(function ($q3) use ($payrollId) {
                        $q3->where('payroll_id', $payrollId);
                    });
                }
            })
            ->orderBy('date')
            ->get(['id', 'date', 'type', 'reason', 'amount', 'notes', 'status', 'payroll_id']);

        // ── Deductions ──
        $deductions = HrDeduction::where('branch_id', $branchId)
            ->where('employee_id', $empId)
            ->whereYear('applied_month', $monthStart->year)
            ->whereMonth('applied_month', $monthStart->month)
            ->where(function ($q) use ($payrollId) {
                $q->where(function ($q2) {
                    $q2->whereNull('payroll_id')
                        ->where(function ($qq) {
                            $qq->where('status', 'approved')->orWhere('status', 1)->orWhere('status', '1');
                        });
                });
                if ($payrollId) {
                    $q->orWhere(function ($q3) use ($payrollId) {
                        $q3->where('payroll_id', $payrollId);
                    });
                }
            })
            ->orderBy('date')
            ->get(['id', 'date', 'type', 'reason', 'amount', 'notes', 'status', 'payroll_id']);

        // ── Advances ──
        $advances = HrAdvanceInstallment::where('employee_id', $empId)
            ->whereDate('month', $monthStart->toDateString())
            ->where(function ($q) use ($payrollId) {
                $q->whereNull('payroll_id');
                if ($payrollId) $q->orWhere('payroll_id', $payrollId);
            })
            ->orderBy('id')
            ->get(['id', 'advance_id', 'amount', 'is_paid', 'payroll_id'])
            ->map(function ($r) {
                return [
                    'id'          => $r->id,
                    'advance_id'  => $r->advance_id,
                    'amount'      => (float)$r->amount,
                    'status_text' => ($r->is_paid ? 'paid' : 'unpaid'),
                ];
            });

        // ── ✅ Attendance rows + KPI ──
        $att = HrAttendance::where('branch_id', $branchId)
            ->where('employee_id', $empId)
            ->whereDate('date', '>=', $monthStart->toDateString())
            ->whereDate('date', '<=', $monthEnd->toDateString())
            ->orderBy('date')
            ->get(['id', 'date', 'check_in', 'check_out', 'total_hours', 'status', 'source', 'notes']);

        $workDays = (int)self::WORK_DAYS;

        $attKpi = [
            'work_days'    => $workDays,
            'present_days' => 0,
            'late_days'    => 0,
            'halfday_days' => 0,
            'absent_days'  => 0,
            'total_hours'  => round((float)$att->sum('total_hours'), 2),

            // money fields (will be filled below)
            'day_rate'          => 0,
            'present_amount'    => 0,
            'halfday_amount'    => 0,
            'attendance_amount' => 0,
        ];

        foreach ($att as $a) {
            $s = strtolower(trim((string)($a->status ?? '')));
            if ($s === 'late') {
                $attKpi['late_days']++;
                $attKpi['present_days']++; // late محسوب ضمن الحضور
            } elseif ($s === 'present') {
                $attKpi['present_days']++;
            } elseif ($s === 'absent') {
                $attKpi['absent_days']++;
            } elseif ($s === 'halfday' || $s === 'half_day') {
                $attKpi['halfday_days']++;
            }
        }

        // ✅ حساب سعر اليوم وقيمة الحضور
        $baseSalary = (float)($payroll?->base_salary ?? 0);
        if ($baseSalary <= 0) {
            $emp = \App\Models\employee\employee::find($empId);
            $baseSalary = (float)($emp?->base_salary ?? 0);
        }

        $dayRate  = ($baseSalary > 0 && $workDays > 0) ? round($baseSalary / $workDays, 2) : 0;
        $halfRate = round($dayRate / 2, 2);

        $presentAmount = round(((int)$attKpi['present_days']) * $dayRate, 2);
        $halfdayAmount = round(((int)$attKpi['halfday_days']) * $halfRate, 2);
        $attendanceAmount = round($presentAmount + $halfdayAmount, 2);

        $attKpi['day_rate'] = $dayRate;
        $attKpi['present_amount'] = $presentAmount;
        $attKpi['halfday_amount'] = $halfdayAmount;
        $attKpi['attendance_amount'] = $attendanceAmount;

        $attRows = $att->map(function ($a) {
            return [
                'date'        => $a->date      ? Carbon::parse($a->date)->format('Y-m-d')      : null,
                'check_in'    => $a->check_in  ? Carbon::parse($a->check_in)->format('H:i')    : null,
                'check_out'   => $a->check_out ? Carbon::parse($a->check_out)->format('H:i')   : null,
                'total_hours' => round((float)($a->total_hours ?? 0), 2),
                'status'      => $a->status,
                'source'      => $a->source,
                'notes'       => $a->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'payroll_id'     => $payrollId,
                'sum_overtime'   => round((float)$overtimes->sum('total_amount'), 2),
                'sum_allowances' => round((float)$allowances->sum('amount'), 2),
                'sum_deductions' => round((float)$deductions->sum('amount'), 2),
                'sum_advances'   => round((float)$advances->sum('amount'), 2),

                'overtimes'  => $overtimes,
                'allowances' => $allowances,
                'deductions' => $deductions,
                'advances'   => $advances,

                'attendance_kpi'  => $attKpi,
                'attendance_rows' => $attRows,
            ],
        ]);
    }


    // ─────────────────────────────────────────
    // Helpers

    private function computePayrollKpis($rows): array
    {
        $rows = $rows ?: collect();

        return [
            'total_rows'     => (int)$rows->count(),
            'draft'          => (int)$rows->where('status', 'draft')->count(),
            'approved'       => (int)$rows->where('status', 'approved')->count(),
            'paid'           => (int)$rows->where('status', 'paid')->count(),
            'sum_base'       => round((float)$rows->sum('base_salary'),        2),
            'sum_overtime'   => round((float)$rows->sum('overtime_amount'),    2),
            'sum_allowances' => round((float)$rows->sum('allowances_amount'),  2),
            'sum_advances'   => round((float)$rows->sum('advances_deduction'), 2),
            'sum_deductions' => round((float)$rows->sum('deductions_amount'),  2),
            'sum_gross'      => round((float)$rows->sum('gross_salary'),       2),
            'sum_net'        => round((float)$rows->sum('net_salary'),         2),
        ];
    }

    private function buildPayrollsExcelHtml($rows): string
    {
        $rows = $rows ?: collect();

        $html  = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0">';
        $html .= '<tr>'
            . '<th>#</th><th>Employee</th><th>Month</th><th>Base</th><th>Overtime</th><th>Allowances</th>'
            . '<th>Advances</th><th>Deductions</th><th>Net</th><th>Payment Method</th><th>Payment Details</th>'
            . '<th>Status</th><th>Payment Date</th>'
            . '</tr>';

        $i = 0;
        foreach ($rows as $r) {
            $i++;
            $month = $r->month ? Carbon::parse($r->month)->format('Y-m') : '';
            $html .= '<tr>'
                . '<td>' . $i . '</td>'
                . '<td>' . e(($r->employee?->full_name ?? '') . ' (' . ($r->employee?->code ?? '') . ')') . '</td>'
                . '<td>' . e($month) . '</td>'
                . '<td>' . number_format((float)$r->base_salary,        2) . '</td>'
                . '<td>' . number_format((float)$r->overtime_amount,    2) . '</td>'
                . '<td>' . number_format((float)$r->allowances_amount,  2) . '</td>'
                . '<td>' . number_format((float)$r->advances_deduction, 2) . '</td>'
                . '<td>' . number_format((float)$r->deductions_amount,  2) . '</td>'
                . '<td>' . number_format((float)$r->net_salary,         2) . '</td>'
                . '<td>' . e($r->payment_method          ?? '') . '</td>'
                . '<td>' . e($r->salary_transfer_details ?? '') . '</td>'
                . '<td>' . e($r->status                  ?? '') . '</td>'
                . '<td>' . e($r->payment_date ? Carbon::parse($r->payment_date)->toDateString() : '') . '</td>'
                . '</tr>';
        }

        $html .= '</table></body></html>';
        return $html;
    }

    private function computeMonthAmounts(int $employeeId, int $branchId, Carbon $monthStart): array
    {
        $approved = function ($q) {
            $q->where('status', 'approved')->orWhere('status', 1)->orWhere('status', '1');
        };

        $overtime = (float) HrOvertime::where('employee_id', $employeeId)
            ->where('branch_id', $branchId)
            ->whereYear('applied_month', $monthStart->year)
            ->whereMonth('applied_month', $monthStart->month)
            ->where(function ($q) use ($approved) {
                $approved($q);
            })
            ->whereNull('payroll_id')
            ->sum('total_amount');

        $allowances = (float) HrAllowance::where('employee_id', $employeeId)
            ->where('branch_id', $branchId)
            ->whereYear('applied_month', $monthStart->year)
            ->whereMonth('applied_month', $monthStart->month)
            ->where(function ($q) use ($approved) {
                $approved($q);
            })
            ->whereNull('payroll_id')
            ->sum('amount');

        $deductions = (float) HrDeduction::where('employee_id', $employeeId)
            ->where('branch_id', $branchId)
            ->whereYear('applied_month', $monthStart->year)
            ->whereMonth('applied_month', $monthStart->month)
            ->where(function ($q) use ($approved) {
                $approved($q);
            })
            ->whereNull('payroll_id')
            ->sum('amount');

        $advances = (float) HrAdvanceInstallment::where('employee_id', $employeeId)
            ->whereDate('month', $monthStart->toDateString())
            ->where('is_paid', false)
            ->whereNull('payroll_id')
            ->sum('amount');

        return [
            'overtime_amount'   => round($overtime,   2),
            'allowances_amount' => round($allowances, 2),
            'deductions_amount' => round($deductions, 2),
            'advances_deduction' => round($advances,   2),
        ];
    }

    private function getEmployeesByPrimaryBranch(int $branchId)
    {
        if ($branchId <= 0) return collect([]);

        return employee::where('status', 1)
            ->where('base_salary', '>', 0)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId)
                    ->where('employee_branch.is_primary', 1);
            })
            ->orderBy('id')
            ->get();
    }

    private function computeAttendanceDeductionsPreview(
        int    $employeeId,
        int    $branchId,
        string $dateFrom,
        string $dateTo,
        float  $baseSalary,
        Carbon $monthStart
    ): float {
        if ($baseSalary <= 0) return 0.0;

        $counts = $this->getAttendanceCounts($employeeId, $branchId, $dateFrom, $dateTo);
        $calc   = $this->calcAttendanceDeductions($baseSalary, $counts['present'], $counts['halfday'], $counts['absent']);

        $existingAuto = (float) HrDeduction::where('employee_id', $employeeId)
            ->where('branch_id', $branchId)
            ->whereYear('applied_month', $monthStart->year)
            ->whereMonth('applied_month', $monthStart->month)
            ->where('type', 'penalty')
            ->where('notes', 'like', self::AUTO_ATTENDANCE_NOTES . '%')
            ->whereNull('payroll_id')
            ->sum('amount');

        return round(max(0, $calc['total'] - $existingAuto), 2);
    }

    private function createOrReplaceMonthlyAttendanceDeductions(HrPayroll $payroll, string $dateFrom, string $dateTo): void
    {
        $empId      = (int)$payroll->employee_id;
        $branchId   = (int)$payroll->branch_id;
        $monthStart = Carbon::parse($payroll->month)->startOfMonth();

        $base = (float)($payroll->base_salary ?? 0);
        if ($base <= 0) return;

        HrDeduction::where('employee_id', $empId)
            ->where('branch_id', $branchId)
            ->whereYear('applied_month', $monthStart->year)
            ->whereMonth('applied_month', $monthStart->month)
            ->where('type', 'penalty')
            ->where('notes', 'like', self::AUTO_ATTENDANCE_NOTES . '%')
            ->whereNull('payroll_id')
            ->delete();

        $counts         = $this->getAttendanceCounts($empId, $branchId, $dateFrom, $dateTo);
        $calc           = $this->calcAttendanceDeductions($base, $counts['present'], $counts['halfday'], $counts['absent']);
        $appliedMonthDate = $monthStart->toDateString();

        if ($calc['absent_days'] > 0 && $calc['absent_amount'] > 0) {
            $d              = new HrDeduction();
            $d->employee_id  = $empId;
            $d->branch_id    = $branchId;
            $d->type         = 'penalty';
            $d->reason       = 'خصم غياب (' . $calc['absent_days'] . ' يوم)';
            $d->amount       = round($calc['absent_amount'], 2);
            $d->date         = $appliedMonthDate;
            $d->applied_month = $appliedMonthDate;
            $d->status       = 'approved';
            $d->payroll_id   = null;
            $d->notes        = self::AUTO_ATTENDANCE_NOTES . ' | absent';
            $d->user_add     = Auth::id();
            $d->save();
        }

        if ($calc['halfday_count'] > 0 && $calc['halfday_amount'] > 0) {
            $d              = new HrDeduction();
            $d->employee_id  = $empId;
            $d->branch_id    = $branchId;
            $d->type         = 'penalty';
            $d->reason       = 'خصم نصف يوم (' . $calc['halfday_count'] . ')';
            $d->amount       = round($calc['halfday_amount'], 2);
            $d->date         = $appliedMonthDate;
            $d->applied_month = $appliedMonthDate;
            $d->status       = 'approved';
            $d->payroll_id   = null;
            $d->notes        = self::AUTO_ATTENDANCE_NOTES . ' | halfday';
            $d->user_add     = Auth::id();
            $d->save();
        }
    }

    private function getAttendanceCounts(int $employeeId, int $branchId, string $dateFrom, string $dateTo): array
    {
        $row = HrAttendance::where('employee_id', $employeeId)
            ->where('branch_id', $branchId)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->selectRaw("
                SUM(CASE WHEN status IN ('present','late')         THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status IN ('halfday','half_day')     THEN 1 ELSE 0 END) AS halfday_count,
                SUM(CASE WHEN status = 'absent'                    THEN 1 ELSE 0 END) AS absent_count
            ")
            ->first();

        return [
            'present' => (int)($row->present_count ?? 0),
            'halfday' => (int)($row->halfday_count ?? 0),
            'absent'  => (int)($row->absent_count  ?? 0),
        ];
    }

    private function calcAttendanceDeductions(float $baseSalary, int $presentCount, int $halfdayCount, int $absentCount): array
    {
        $dayRate  = round($baseSalary / self::WORK_DAYS, 2);
        $halfRate = round($dayRate / 2, 2);

        $attendedRecords = $presentCount + $halfdayCount;
        $missingDays     = max(0, self::WORK_DAYS - ($attendedRecords + $absentCount));
        $absentDays      = $absentCount + $missingDays;

        $absentAmount  = round($absentDays    * $dayRate,  2);
        $halfdayAmount = round($halfdayCount  * $halfRate, 2);

        return [
            'present_count'  => $presentCount,
            'halfday_count'  => $halfdayCount,
            'absent_count'   => $absentCount,
            'missing_days'   => $missingDays,
            'absent_days'    => $absentDays,
            'absent_amount'  => $absentAmount,
            'halfday_amount' => $halfdayAmount,
            'total'          => round($absentAmount + $halfdayAmount, 2),
        ];
    }

    // ─────────────────────────────────────────
    // Auto overtime from attendance

    private function autoGenerateOvertimeFromAttendance(int $branchId, string $dateFrom, string $dateTo, $employees): array
    {
        $employeeIds = $employees->pluck('id')->toArray();

        $created        = 0;
        $skippedExists  = 0;
        $skippedNoShift = 0;
        $skippedNoTimes = 0;
        $skippedZero    = 0;

        if (empty($employeeIds)) {
            return compact('created', 'skippedExists', 'skippedNoShift', 'skippedNoTimes', 'skippedZero');
        }

        $attRows = HrAttendance::where('branch_id', $branchId)
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'present')
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get();

        $shiftCache = [];

        foreach ($attRows as $att) {
            $empId   = (int)$att->employee_id;
            $attDate = Carbon::parse($att->date)->toDateString();

            $exists = HrOvertime::where('employee_id', $empId)
                ->where('branch_id', $branchId)
                ->where(function ($q) use ($attDate, $att) {
                    $q->whereDate('date', $attDate)->orWhere('attendance_id', $att->id);
                })
                ->exists();

            if ($exists) {
                $skippedExists++;
                continue;
            }

            $cacheKey = $empId . '|' . $attDate;
            if (!array_key_exists($cacheKey, $shiftCache)) {
                $es = HrEmployeeShift::with('shift')
                    ->where('employee_id', $empId)
                    ->where('branch_id', $branchId)
                    ->where('status', 1)
                    ->where(function ($q) use ($attDate) {
                        $q->whereNull('start_date')->orWhereDate('start_date', '<=', $attDate);
                    })
                    ->where(function ($q) use ($attDate) {
                        $q->whereNull('end_date')->orWhereDate('end_date', '>=', $attDate);
                    })
                    ->orderByDesc('id')
                    ->first();

                $shiftCache[$cacheKey] = $es;
            }

            $shift = $shiftCache[$cacheKey]?->shift;
            if (!$shift || !$shift->start_time || !$shift->end_time) {
                $skippedNoShift++;
                continue;
            }

            $inRaw  = $att->getRawOriginal('check_in')  ?? $att->check_in;
            $outRaw = $att->getRawOriginal('check_out') ?? $att->check_out;

            $inTime  = $this->extractTimeOnly($inRaw);
            $outTime = $this->extractTimeOnly($outRaw);

            if (!$inTime || !$outTime) {
                $skippedNoTimes++;
                continue;
            }

            $actualIn  = Carbon::parse($attDate . ' ' . $inTime);
            $actualOut = Carbon::parse($attDate . ' ' . $outTime);
            if ($actualOut->lessThanOrEqualTo($actualIn)) $actualOut->addDay();

            $workedMinutes = max(0, $actualIn->diffInMinutes($actualOut));

            $scheduledStart = Carbon::parse($attDate . ' ' . $shift->start_time);
            $scheduledEnd   = Carbon::parse($attDate . ' ' . $shift->end_time);
            if ($scheduledEnd->lessThanOrEqualTo($scheduledStart)) $scheduledEnd->addDay();

            $isWorkingDay = $this->isWorkingDay($shift, $attDate);

            $otMinutes = 0;
            if (!$isWorkingDay) {
                $otMinutes = $workedMinutes;
            } else {
                if ($actualOut->greaterThan($scheduledEnd)) {
                    $otMinutes = $scheduledEnd->diffInMinutes($actualOut);
                }
            }

            $otHours = $this->roundToHalfHour($otMinutes / 60);
            if ($otHours <= 0) {
                $skippedZero++;
                continue;
            }

            $emp        = $employees->firstWhere('id', $empId) ?: employee::find($empId);
            $baseSalary = (float)($emp?->base_salary ?? 0);

            $shiftHours = $this->calcShiftHoursFromShift($attDate, (string)$shift->start_time, (string)$shift->end_time);
            if ($shiftHours <= 0) $shiftHours = self::DEFAULT_SHIFT_HOURS;

            $rate  = ($baseSalary > 0)
                ? round(($baseSalary / 26 / $shiftHours) * self::OT_MULTIPLIER, 2)
                : 0.0;

            $total        = round($otHours * $rate, 2);
            $appliedMonth = Carbon::parse($attDate)->startOfMonth()->toDateString();

            $o               = new HrOvertime();
            $o->employee_id  = $empId;
            $o->branch_id    = $branchId;
            $o->attendance_id = $att->id;
            $o->source       = 'attendance';
            $o->date         = $attDate;
            $o->hours        = round($otHours, 2);
            $o->hour_rate    = round($rate,    2);
            $o->total_amount = round($total,   2);
            $o->applied_month = $appliedMonth;
            $o->status       = 'approved';
            $o->payroll_id   = null;
            $o->notes        = self::AUTO_OT_TAG . ' | attendance_id=' . $att->id;
            $o->user_add     = Auth::id();
            $o->save();

            $created++;
        }

        return [
            'created'          => $created,
            'skipped_exists'   => $skippedExists,
            'skipped_no_shift' => $skippedNoShift,
            'skipped_no_times' => $skippedNoTimes,
            'skipped_zero'     => $skippedZero,
        ];
    }

    private function extractTimeOnly($v): ?string
    {
        if ($v === null || $v === '') return null;

        try {
            if ($v instanceof \DateTimeInterface) {
                return Carbon::instance($v)->format('H:i:s');
            }

            $s = (string)$v;

            if (preg_match('/\d{4}-\d{2}-\d{2}/', $s)) {
                return Carbon::parse($s)->format('H:i:s');
            }

            if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $s)) {
                return strlen($s) === 5 ? ($s . ':00') : $s;
            }

            return Carbon::parse($s)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function roundToHalfHour(float $hours): float
    {
        if (!is_finite($hours) || $hours <= 0) return 0.0;
        return round(round($hours * 2) / 2, 2);
    }

    private function isWorkingDay($shift, string $date): bool
    {
        $dow    = Carbon::parse($date)->dayOfWeek; // 0=Sun
        $fields = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $field  = $fields[$dow] ?? 'sun';
        return (bool)($shift->{$field} ?? false);
    }

    private function calcShiftHoursFromShift(string $date, string $startTime, string $endTime): float
    {
        try {
            $start = Carbon::parse($date . ' ' . $startTime);
            $end   = Carbon::parse($date . ' ' . $endTime);
            if ($end->lessThanOrEqualTo($start)) $end->addDay();
            return round(max(0.0, $start->diffInMinutes($end) / 60), 2);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }
}
