<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\hr\HrOvertime;
use App\Models\hr\HrAttendance;
use App\Models\hr\HrAttendanceLog;
use App\Models\hr\HrEmployeeShift;
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
    // تحسين: لو أرسلت date سيتم حساب hour_rate بناءً على وردية اليوم (اختياري)
    public function employeesByBranch(Request $request)
    {
        $branchId = (int)$request->get('branch_id', 0);
        $date = (string)$request->get('date', ''); // optional YYYY-MM-DD

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        $d = null;
        if ($date) {
            try { $d = Carbon::parse($date)->toDateString(); } catch (\Throwable $e) { $d = null; }
        }
        if (!$d) $d = Carbon::now()->toDateString();

        $data = $employees->map(function ($e) use ($branchId, $d) {
            $base = (float)($e->base_salary ?? 0);
            $shiftHours = HrOvertime::getEmployeeShiftHours((int)$e->id, $branchId, $d, 8.0);

            return [
                'id' => $e->id,
                'name' => $e->full_name ?? $e->getFullNameAttribute(),
                'code' => $e->code,
                'base_salary' => round($base, 2),
                'shift_hours' => round($shiftHours, 2),
                'hour_rate' => HrOvertime::calcHourRate($base, $shiftHours, 2.0),
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

            // ✅ defaultRate based on shift hours
            $shiftHours = HrOvertime::getEmployeeShiftHours($employeeId, $branchId, $date, 8.0);
            $defaultRate = HrOvertime::calcHourRate((float)($emp->base_salary ?? 0), $shiftHours, 2.0);

            $rate = isset($data['hour_rate']) && $data['hour_rate'] !== null && $data['hour_rate'] !== ''
                ? round((float)$data['hour_rate'], 2)
                : $defaultRate;

            $total = round($hours * $rate, 2);

            $o = new HrOvertime();
            $o->employee_id   = $employeeId;
            $o->branch_id     = $branchId;
            $o->attendance_id = null;
            $o->source        = 'manual';
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

            // ✅ defaultRate based on shift hours
            $shiftHours = HrOvertime::getEmployeeShiftHours($employeeId, $branchId, $date, 8.0);
            $defaultRate = HrOvertime::calcHourRate((float)($emp->base_salary ?? 0), $shiftHours, 2.0);

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
    // Generate overtime from attendance
    public function generateFromAttendance(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);

        $branchId = (int)$data['branch_id'];
        $dateFrom = Carbon::parse($data['date_from'])->toDateString();
        $dateTo   = Carbon::parse($data['date_to'])->toDateString();

        $employees = $this->getEmployeesByPrimaryBranch($branchId);
        $employeeIds = $employees->pluck('id')->toArray();

        if (empty($employeeIds)) {
            return response()->json(['success' => true, 'message' => trans('hr.no_data') ?? 'لا يوجد بيانات', 'data' => [
                'created' => 0,
                'skipped_exists' => 0,
                'skipped_no_shift' => 0,
                'skipped_no_time' => 0,
                'skipped_zero' => 0,
            ]]);
        }

        $created = 0;
        $skippedExists = 0;
        $skippedNoShift = 0;
        $skippedNoTime = 0;
        $skippedZero = 0;

        $shiftCache = [];

        try {
            DB::beginTransaction();

            $attRows = HrAttendance::where('branch_id', $branchId)
                ->whereIn('employee_id', $employeeIds)
                ->where('status', 'present')
                ->whereDate('date', '>=', $dateFrom)
                ->whereDate('date', '<=', $dateTo)
                ->orderBy('date')
                ->orderBy('employee_id')
                ->get();

            foreach ($attRows as $att) {
                $empId = (int)$att->employee_id;
                $attDate = Carbon::parse($att->date)->toDateString();

                $exists = HrOvertime::where('employee_id', $empId)
                    ->where('branch_id', $branchId)
                    ->whereDate('date', $attDate)
                    ->exists();

                if ($exists) {
                    $skippedExists++;
                    continue;
                }

                // Shift
                $cacheKey = $empId . '|' . $attDate;
                if (!array_key_exists($cacheKey, $shiftCache)) {
                    $es = HrEmployeeShift::with('shift')
                        ->where('employee_id', $empId)
                        ->where('branch_id', $branchId)
                        ->where('status', 1)
                        ->whereDate('start_date', '<=', $attDate)
                        ->where(function ($q) use ($attDate) {
                            $q->whereNull('end_date')->orWhereDate('end_date', '>=', $attDate);
                        })
                        ->first();

                    $shiftCache[$cacheKey] = $es;
                }

                $employeeShift = $shiftCache[$cacheKey];
                $shift = $employeeShift?->shift;

                if (!$shift) {
                    $skippedNoShift++;
                    continue;
                }

                $isWorkingDay = $this->isWorkingDay($shift, $attDate);

                [$actualIn, $actualOut] = $this->getActualInOut($att);
                if (!$actualIn || !$actualOut) {
                    $skippedNoTime++;
                    continue;
                }

                $workedMinutes = max(0, $actualIn->diffInMinutes($actualOut));

                $scheduledStart = Carbon::parse($attDate . ' ' . $shift->start_time);
                $scheduledEnd   = Carbon::parse($attDate . ' ' . $shift->end_time);
                if ($scheduledEnd->lessThanOrEqualTo($scheduledStart)) {
                    $scheduledEnd->addDay(); // night shift
                }

                $overtimeMinutes = 0;

                if (!$isWorkingDay) {
                    $overtimeMinutes = $workedMinutes;
                } else {
                    if ($actualOut->greaterThan($scheduledEnd)) {
                        $overtimeMinutes = $scheduledEnd->diffInMinutes($actualOut);
                    }
                }

                $overtimeHours = $this->roundToHalfHour($overtimeMinutes / 60);

                if ($overtimeHours <= 0) {
                    $skippedZero++;
                    continue;
                }

                $emp = $employees->firstWhere('id', $empId) ?: employee::find($empId);
                $baseSalary = (float)($emp?->base_salary ?? 0);

                // ✅ hour rate based on shift duration
                $shiftHours = HrOvertime::calcShiftHours($attDate, (string)$shift->start_time, (string)$shift->end_time);
                if ($shiftHours <= 0) $shiftHours = 8.0;

                $rate = ($baseSalary > 0) ? HrOvertime::calcHourRate($baseSalary, $shiftHours, 2.0) : 0.00;
                $total = round($overtimeHours * $rate, 2);

                $appliedMonth = Carbon::parse($attDate)->startOfMonth()->toDateString();

                $o = new HrOvertime();
                $o->employee_id   = $empId;
                $o->branch_id     = $branchId;
                $o->attendance_id = $att->id;
                $o->source        = 'attendance';
                $o->date          = $attDate;
                $o->hours         = round($overtimeHours, 2);
                $o->hour_rate     = round($rate, 2);
                $o->total_amount  = round($total, 2);
                $o->applied_month = $appliedMonth;
                $o->status        = 'pending';
                $o->payroll_id    = null;
                $o->notes         = $o->notes ?: 'Auto from attendance #' . $att->id;
                $o->user_add      = Auth::id();
                $o->save();

                $created++;
            }

            DB::commit();

            $msg = (trans('hr.overtime_generated_success') ?? 'تم توليد الوقت الإضافي بنجاح')
                . ' | Created: ' . $created
                . ' | Exists: ' . $skippedExists
                . ' | NoShift: ' . $skippedNoShift
                . ' | NoTime: ' . $skippedNoTime
                . ' | Zero: ' . $skippedZero;

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => [
                    'created' => $created,
                    'skipped_exists' => $skippedExists,
                    'skipped_no_shift' => $skippedNoShift,
                    'skipped_no_time' => $skippedNoTime,
                    'skipped_zero' => $skippedZero,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('overtime.generateFromAttendance error', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
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

            'attendance_id' => $o->attendance_id,
            'source'        => $o->source ?? 'manual',

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

    private function roundToHalfHour(float $hours): float
    {
        if (!is_finite($hours) || $hours <= 0) return 0.0;
        $rounded = round($hours * 2) / 2; // nearest 0.5
        return round($rounded, 2);
    }

    private function isWorkingDay($shift, string $date): bool
    {
        $dow = Carbon::parse($date)->dayOfWeek; // 0=Sun .. 6=Sat
        $fields = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $field = $fields[$dow] ?? 'sun';
        return (bool)($shift->{$field} ?? false);
    }

    private function getActualInOut(HrAttendance $att): array
    {
        $attDate = Carbon::parse($att->date)->toDateString();

        $logs = HrAttendanceLog::where('attendance_id', $att->id)
            ->orderBy('punch_time')
            ->get();

        $inTime = null;
        $outTime = null;

        if ($logs->count() > 0) {
            $inLog = $logs->first(function ($l) {
                $t = strtolower((string)($l->punch_type ?? ''));
                return $t === 'in';
            });

            $outLog = $logs->reverse()->first(function ($l) {
                $t = strtolower((string)($l->punch_type ?? ''));
                return $t === 'out';
            });

            if ($inLog && $inLog->punch_time) $inTime = Carbon::parse($inLog->punch_time)->format('H:i:s');
            if ($outLog && $outLog->punch_time) $outTime = Carbon::parse($outLog->punch_time)->format('H:i:s');

            if (!$inTime && $logs->first()?->punch_time) $inTime = Carbon::parse($logs->first()->punch_time)->format('H:i:s');
            if (!$outTime && $logs->last()?->punch_time) $outTime = Carbon::parse($logs->last()->punch_time)->format('H:i:s');
        }

        if (!$inTime && $att->check_in) {
            try { $inTime = Carbon::parse($att->check_in)->format('H:i:s'); } catch (\Throwable $e) {}
        }
        if (!$outTime && $att->check_out) {
            try { $outTime = Carbon::parse($att->check_out)->format('H:i:s'); } catch (\Throwable $e) {}
        }

        if (!$inTime || !$outTime) return [null, null];

        $actualIn  = Carbon::parse($attDate . ' ' . $inTime);
        $actualOut = Carbon::parse($attDate . ' ' . $outTime);

        if ($actualOut->lessThanOrEqualTo($actualIn)) {
            $actualOut->addDay();
        }

        return [$actualIn, $actualOut];
    }
}
