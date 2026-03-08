<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\general\Branch;
use App\Models\hr\HrAttendance;
use App\Models\hr\HrAttendanceLog;
use App\Models\hr\HrDevice;
use App\Models\hr\HrEmployeeShift;
use App\Models\hr\HrShift;
use App\Models\employee\employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class attendancecontroller extends Controller
{
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

        $mode          = $request->get('mode', 'daily');
        $defaultBranch = !empty($branchIds) ? $branchIds[0] : 0;
        $branchId      = (int)($request->get('branch_id', $defaultBranch));
        $employeeId    = (int)($request->get('employee_id', 0));

        // ✅ التحقق أن الفرع المطلوب ضمن فروع المستخدم
        if ($branchId > 0 && !$this->userCanAccessBranch($branchId)) {
            $branchId = $defaultBranch;
        }

        $today = Carbon::today();
        $month = $request->get('month', $today->format('Y-m'));
        $date  = $request->get('date', $today->toDateString());

        $employees = $this->getEmployeesByPrimaryBranch($branchId);

        if ($employeeId > 0) {
            $employees = $employees->where('id', $employeeId)->values();
        }

        $rows = ($mode === 'daily')
            ? $this->buildDailyRows($branchId, $employees, $date)
            : $this->buildMonthlyRows($branchId, $employees, $month);

        return view('hr.attendance.index', compact(
            'branches', 'employees', 'rows',
            'mode', 'branchId', 'employeeId', 'month', 'date'
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
    // Store
    // ─────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'branch_id'   => 'required|exists:branches,id',
            'date'        => 'required|date',
            'check_in'    => 'nullable|date_format:H:i',
            'check_out'   => 'nullable|date_format:H:i',
            'notes'       => 'nullable|string|max:1000',
        ]);

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if (!$this->userCanAccessBranch((int)$data['branch_id'])) {
            return response()->json(['success' => false, 'message' => trans('accounting.branch_not_allowed')], 403);
        }

        $employee = employee::findOrFail($data['employee_id']);

        if (!$this->isEmployeePrimaryInBranch($employee->id, (int)$data['branch_id'])) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        $shift = $this->getEmployeeShiftForDate($employee->id, (int)$data['branch_id'], $data['date']);

        $checkInDT  = $data['check_in']  ? Carbon::parse($data['date'] . ' ' . $data['check_in'])  : null;
        $checkOutDT = $data['check_out'] ? Carbon::parse($data['date'] . ' ' . $data['check_out']) : null;

        [$checkInDT, $checkOutDT] = $this->normalizeCheckTimes($shift, $data['date'], $checkInDT, $checkOutDT);

        $attendance = HrAttendance::where('employee_id', $employee->id)
            ->whereDate('date', $data['date'])
            ->first();

        if (!$attendance) {
            $attendance              = new HrAttendance();
            $attendance->employee_id = $employee->id;
            $attendance->branch_id   = (int)$data['branch_id'];
            $attendance->date        = $data['date'];
            $attendance->source      = 'manual';
            $attendance->user_add    = Auth::id();
        }

        $attendance->check_in    = $checkInDT;
        $attendance->check_out   = $checkOutDT;
        $attendance->notes       = $data['notes'] ?? null;
        $attendance->total_hours = $this->calcHours($attendance->check_in, $attendance->check_out, $shift, $attendance->date);
        $attendance->status      = $this->normalizeAttendanceStatus(
            $this->calcStatus($shift, $attendance->date, $attendance->check_in, $attendance->check_out, (float)$attendance->total_hours)
        );
        $attendance->source      = $this->normalizeAttendanceSource($attendance->source ?: 'manual');
        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => trans('hr.attendance_saved_success'),
            'data'    => $this->attendanceRowDto($attendance, $employee, $shift, false),
        ]);
    }

    // ─────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────

    public function show($id)
    {
        $att = HrAttendance::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $att->id,
                'employee_id' => $att->employee_id,
                'branch_id'   => $att->branch_id,
                'date'        => Carbon::parse($att->date)->toDateString(),
                'check_in'    => $att->check_in  ? Carbon::parse($att->check_in)->format('H:i')  : null,
                'check_out'   => $att->check_out ? Carbon::parse($att->check_out)->format('H:i') : null,
                'notes'       => $att->notes,
            ],
        ]);
    }

    // ─────────────────────────────────────────
    // Update
    // ─────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $att = HrAttendance::findOrFail($id);

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'branch_id'   => 'required|exists:branches,id',
            'date'        => 'required|date',
            'check_in'    => 'nullable|date_format:H:i',
            'check_out'   => 'nullable|date_format:H:i',
            'notes'       => 'nullable|string|max:1000',
        ]);

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if (!$this->userCanAccessBranch((int)$data['branch_id'])) {
            return response()->json(['success' => false, 'message' => trans('accounting.branch_not_allowed')], 403);
        }

        $employee = employee::findOrFail($data['employee_id']);

        if (!$this->isEmployeePrimaryInBranch($employee->id, (int)$data['branch_id'])) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        $shift = $this->getEmployeeShiftForDate($employee->id, (int)$data['branch_id'], $data['date']);

        $checkInDT  = $data['check_in']  ? Carbon::parse($data['date'] . ' ' . $data['check_in'])  : null;
        $checkOutDT = $data['check_out'] ? Carbon::parse($data['date'] . ' ' . $data['check_out']) : null;

        [$checkInDT, $checkOutDT] = $this->normalizeCheckTimes($shift, $data['date'], $checkInDT, $checkOutDT);

        $att->employee_id = $employee->id;
        $att->branch_id   = (int)$data['branch_id'];
        $att->date        = $data['date'];
        $att->check_in    = $checkInDT;
        $att->check_out   = $checkOutDT;
        $att->notes       = $data['notes'] ?? null;

        if (!$att->source) $att->source = 'manual';

        $att->total_hours = $this->calcHours($att->check_in, $att->check_out, $shift, $att->date);
        $att->status      = $this->normalizeAttendanceStatus(
            $this->calcStatus($shift, $att->date, $att->check_in, $att->check_out, (float)$att->total_hours)
        );
        $att->source      = $this->normalizeAttendanceSource($att->source);
        $att->save();

        return response()->json([
            'success' => true,
            'message' => trans('hr.attendance_updated_success'),
            'data'    => $this->attendanceRowDto($att, $employee, $shift, false),
        ]);
    }

    // ─────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────

    public function destroy($id)
    {
        $att        = HrAttendance::findOrFail($id);
        $employee   = employee::find($att->employee_id);
        $shift      = $this->getEmployeeShiftForDate($att->employee_id, $att->branch_id, Carbon::parse($att->date)->toDateString());
        $employeeId = $att->employee_id;
        $branchId   = $att->branch_id;
        $dateStr    = Carbon::parse($att->date)->toDateString();

        $att->delete();

        return response()->json([
            'success' => true,
            'message' => trans('hr.attendance_deleted_success'),
            'data'    => [
                'employee_id' => $employeeId,
                'branch_id'   => $branchId,
                'date'        => $dateStr,
                'absent_row'  => $this->virtualAbsentRowDto($employee, $branchId, $dateStr, $shift),
            ],
        ]);
    }

    // ─────────────────────────────────────────
    // Processing Logs
    // ─────────────────────────────────────────

    public function processIndex(Request $request)
    {
        $branchIds = $this->accessibleBranchIds();

        // ✅ Scope يعمل تلقائياً
        $branches = Branch::where('status', 1)->orderBy('id')->get();
        $devices  = HrDevice::orderBy('id')->get();

        $defaultBranch = !empty($branchIds) ? $branchIds[0] : 0;
        $branchId      = (int)($request->get('branch_id', $defaultBranch));
        $deviceId      = (int)$request->get('device_id', 0);
        $date          = $request->get('date', Carbon::today()->toDateString());

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if ($branchId > 0 && !$this->userCanAccessBranch($branchId)) {
            $branchId = $defaultBranch;
        }

        $from = Carbon::parse($date)->startOfDay();
        $to   = Carbon::parse($date)->addDay()->endOfDay();

        $logsQ = HrAttendanceLog::with(['employee', 'device'])
            ->where('is_processed', false)
            ->whereBetween('punch_time', [$from, $to]);

        if ($deviceId > 0) $logsQ->where('device_id', $deviceId);

        if ($branchId > 0) {
            $employeeIds = $this->getEmployeesByPrimaryBranch($branchId)->pluck('id')->toArray();
            $logsQ->whereIn('employee_id', $employeeIds);
        }

        $logs = $logsQ->orderBy('punch_time')->get();

        return view('hr.attendance.process', compact(
            'branches', 'devices', 'branchId', 'deviceId', 'date', 'logs'
        ));
    }

    public function processRun(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date'      => 'required|date',
            'device_id' => 'nullable|integer',
        ]);

        $branchId = (int)$data['branch_id'];
        $dateStr  = $data['date'];
        $deviceId = (int)($data['device_id'] ?? 0);

        // ✅ التحقق أن الفرع ضمن فروع المستخدم
        if (!$this->userCanAccessBranch($branchId)) {
            return response()->json(['success' => false, 'message' => trans('accounting.branch_not_allowed')], 403);
        }

        $employees   = $this->getEmployeesByPrimaryBranch($branchId);
        $employeeIds = $employees->pluck('id')->toArray();

        $from = Carbon::parse($dateStr)->startOfDay();
        $to   = Carbon::parse($dateStr)->addDay()->endOfDay();

        $logsQ = HrAttendanceLog::unprocessed()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('punch_time', [$from, $to]);

        if ($deviceId > 0) $logsQ->where('device_id', $deviceId);

        $logs    = $logsQ->orderBy('punch_time')->get()->groupBy('employee_id');
        $created = 0;
        $updated = 0;
        $processedLogs = 0;

        DB::beginTransaction();
        try {
            foreach ($employeeIds as $empId) {
                $employee = $employees->firstWhere('id', (int)$empId);
                if (!$employee) continue;

                $shift = $this->getEmployeeShiftForDate((int)$empId, $branchId, $dateStr);
                if (!$shift) continue;

                [$winStart, $winEnd] = $this->shiftWindow($shift, $dateStr);

                $empLogs = collect($logs[(int)$empId] ?? [])->filter(function ($l) use ($winStart, $winEnd) {
                    $t = Carbon::parse($l->punch_time);
                    return $t->betweenIncluded($winStart, $winEnd);
                })->values();

                if ($empLogs->count() === 0) continue;

                $first    = $empLogs->first();
                $last     = $empLogs->last();
                $checkIn  = Carbon::parse($first->punch_time);
                $checkOut = $empLogs->count() === 1 ? null : Carbon::parse($last->punch_time);

                $attendance = HrAttendance::where('employee_id', (int)$empId)
                    ->whereDate('date', $dateStr)
                    ->first();

                if (!$attendance) {
                    $attendance              = new HrAttendance();
                    $attendance->employee_id = (int)$empId;
                    $attendance->branch_id   = $branchId;
                    $attendance->date        = $dateStr;
                    $attendance->source      = 'fingerprint';
                    $attendance->user_add    = Auth::id();
                    $created++;
                } else {
                    $updated++;
                }

                $attendance->device_id = $deviceId > 0 ? $deviceId : ($first->device_id ?? null);
                $attendance->check_in  = $checkIn;
                $attendance->check_out = $checkOut;

                [$attendance->check_in, $attendance->check_out] =
                    $this->normalizeCheckTimes($shift, $dateStr, $attendance->check_in, $attendance->check_out);

                $attendance->total_hours = $this->calcHours($attendance->check_in, $attendance->check_out, $shift, $attendance->date);
                $attendance->status      = $this->normalizeAttendanceStatus(
                    $this->calcStatus($shift, $attendance->date, $attendance->check_in, $attendance->check_out, (float)$attendance->total_hours)
                );
                $attendance->source      = $this->normalizeAttendanceSource($attendance->source ?: 'fingerprint');
                $attendance->save();

                foreach ($empLogs as $log) {
                    $log->attendance_id = $attendance->id;
                    $log->is_processed  = true;
                    $log->save();
                    $processedLogs++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('attendance.processRun error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

            $msg = trans('hr.error_occurred');
            if (config('app.debug')) $msg = $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine();

            return response()->json(['success' => false, 'message' => $msg], 500);
        }

        return response()->json([
            'success' => true,
            'message' => trans('hr.logs_processed_success'),
            'data'    => ['created' => $created, 'updated' => $updated, 'logs' => $processedLogs],
        ]);
    }

    // ─────────────────────────────────────────
    // Builders
    // ─────────────────────────────────────────

    private function buildDailyRows(int $branchId, $employees, string $dateStr): array
    {
        $date = Carbon::parse($dateStr)->toDateString();
        $rows = [];

        foreach ($employees as $emp) {
            $shift = $this->getEmployeeShiftForDate($emp->id, $branchId, $date);
            if (!$this->isWorkingDay($shift, $date)) continue;

            $att    = HrAttendance::where('employee_id', $emp->id)->whereDate('date', $date)->first();
            $rows[] = $att
                ? $this->attendanceRowDto($att, $emp, $shift, false)
                : $this->virtualAbsentRowDto($emp, $branchId, $date, $shift);
        }

        return $rows;
    }

    private function buildMonthlyRows(int $branchId, $employees, string $month): array
    {
        $start  = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end    = (clone $start)->endOfMonth();
        $period = CarbonPeriod::create($start, $end);

        $att = HrAttendance::whereIn('employee_id', $employees->pluck('id')->toArray())
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($a) => $a->employee_id . '|' . Carbon::parse($a->date)->toDateString());

        $rows = [];

        foreach ($employees as $emp) {
            foreach ($period as $day) {
                $dateStr = $day->toDateString();
                $shift   = $this->getEmployeeShiftForDate($emp->id, $branchId, $dateStr);
                if (!$this->isWorkingDay($shift, $dateStr)) continue;

                $key    = $emp->id . '|' . $dateStr;
                $rows[] = (isset($att[$key]) && $att[$key]->first())
                    ? $this->attendanceRowDto($att[$key]->first(), $emp, $shift, false)
                    : $this->virtualAbsentRowDto($emp, $branchId, $dateStr, $shift);
            }
        }

        return $rows;
    }

    private function attendanceRowDto(HrAttendance $a, $emp, ?HrShift $shift, bool $isVirtual): array
    {
        $dateStr = Carbon::parse($a->date)->toDateString();

        return [
            'is_virtual'    => $isVirtual ? 1 : 0,
            'attendance_id' => $a->id,
            'row_dom_id'    => 'row-att-' . $a->id,
            'employee_id'   => $emp->id,
            'employee_name' => $emp->full_name ?? $emp->getFullNameAttribute(),
            'employee_code' => $emp->code,
            'branch_id'     => $a->branch_id,
            'date'          => $dateStr,
            'shift_name'    => $shift?->name ?? trans('hr.no_shift'),
            'check_in'      => $a->check_in  ? Carbon::parse($a->check_in)->format('H:i')  : '—',
            'check_out'     => $a->check_out ? Carbon::parse($a->check_out)->format('H:i') : '—',
            'total_hours'   => $a->total_hours ? number_format((float)$a->total_hours, 2) : '0.00',
            'status'        => $a->status  ?? 'present',
            'source'        => $a->source  ?? 'system',
            'notes'         => $a->notes   ?? '',
        ];
    }

    private function virtualAbsentRowDto($emp, int $branchId, string $dateStr, ?HrShift $shift): array
    {
        return [
            'is_virtual'    => 1,
            'attendance_id' => null,
            'row_dom_id'    => 'row-emp-' . $emp->id . '-' . $dateStr,
            'employee_id'   => $emp->id,
            'employee_name' => $emp->full_name ?? $emp->getFullNameAttribute(),
            'employee_code' => $emp->code,
            'branch_id'     => $branchId,
            'date'          => $dateStr,
            'shift_name'    => $shift?->name ?? trans('hr.no_shift'),
            'check_in'      => '—',
            'check_out'     => '—',
            'total_hours'   => '0.00',
            'status'        => 'absent',
            'source'        => 'system',
            'notes'         => '',
        ];
    }

    // ─────────────────────────────────────────
    // Night shift helpers
    // ─────────────────────────────────────────

    private function isShiftOvernight(?HrShift $shift): bool
    {
        if (!$shift) return false;

        try {
            $s = Carbon::createFromFormat('H:i', Carbon::parse($shift->start_time)->format('H:i'));
            $e = Carbon::createFromFormat('H:i', Carbon::parse($shift->end_time)->format('H:i'));
            return $e->lessThanOrEqualTo($s);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function shiftWindow(HrShift $shift, string $dateStr): array
    {
        $dateStr = Carbon::parse($dateStr)->toDateString();

        $start = Carbon::parse($dateStr . ' ' . Carbon::parse($shift->start_time)->format('H:i:s'));
        $end   = Carbon::parse($dateStr . ' ' . Carbon::parse($shift->end_time)->format('H:i:s'));

        if ($this->isShiftOvernight($shift)) $end->addDay();

        $start = (clone $start)->subHours(4);
        $end   = (clone $end)->addHours(6);

        $dayStart = Carbon::parse($dateStr)->startOfDay();
        if ($start->lessThan($dayStart)) $start = $dayStart;

        return [$start, $end];
    }

    private function normalizeCheckTimes(?HrShift $shift, string $dateStr, $checkIn, $checkOut): array
    {
        if (!$shift || !$checkOut) return [$checkIn, $checkOut];

        $dateStr   = Carbon::parse($dateStr)->toDateString();
        $overnight = $this->isShiftOvernight($shift);
        $out       = Carbon::parse($checkOut);

        if ($overnight) {
            $endTime = Carbon::parse($shift->end_time)->format('H:i');
            $outTime = $out->format('H:i');

            if ($out->toDateString() === $dateStr && $outTime <= $endTime) {
                $out->addDay();
            }
        }

        if ($checkIn) {
            $in = Carbon::parse($checkIn);
            if ($overnight && $out->lessThanOrEqualTo($in)) $out->addDay();
        }

        return [$checkIn ? Carbon::parse($checkIn) : null, $out];
    }

    // ─────────────────────────────────────────
    // Core calculations
    // ─────────────────────────────────────────

    private function calcHours($checkIn, $checkOut, ?HrShift $shift = null, ?string $attendanceDate = null): float
    {
        if (!$checkIn || !$checkOut) return 0;

        $in  = Carbon::parse($checkIn);
        $out = Carbon::parse($checkOut);

        if ($out->lessThanOrEqualTo($in)) {
            if ($shift && $this->isShiftOvernight($shift)) {
                $out = (clone $out)->addDay();
            } else {
                return 0;
            }
        }

        return round($out->diffInMinutes($in) / 60, 2);
    }

    private function calcStatus(?HrShift $shift, $date, $checkIn, $checkOut, float $hours): string
    {
        $dateStr = Carbon::parse($date)->toDateString();

        if (!$shift) return $checkIn ? 'present' : 'absent';
        if (!$this->isWorkingDay($shift, $dateStr)) return 'absent';
        if (!$checkIn) return 'absent';

        $scheduledIn  = Carbon::parse($dateStr . ' ' . Carbon::parse($shift->start_time)->format('H:i:s'));
        $scheduledOut = Carbon::parse($dateStr . ' ' . Carbon::parse($shift->end_time)->format('H:i:s'));

        if ($this->isShiftOvernight($shift)) $scheduledOut->addDay();

        $graceIn = (clone $scheduledIn)->addMinutes((int)$shift->grace_minutes);
        $status  = 'present';

        if (Carbon::parse($checkIn)->greaterThan($graceIn)) $status = 'late';
        if ($checkIn && !$checkOut)                          $status = 'half_day';
        if ($hours > 0 && $hours < (float)$shift->min_full_hours) $status = 'half_day';

        return $status;
    }

    private function normalizeAttendanceStatus(string $status): string
    {
        $status  = strtolower(trim($status));
        $allowed = ['present', 'absent', 'late', 'half_day', 'leave'];

        if (in_array($status, $allowed, true)) return $status;

        $map = ['halfday' => 'half_day', 'half-day' => 'half_day', 'half day' => 'half_day'];

        return $map[$status] ?? 'present';
    }

    private function normalizeAttendanceSource(string $source): string
    {
        $source  = strtolower(trim($source));
        $allowed = ['fingerprint', 'manual', 'system'];

        if (in_array($source, $allowed, true)) return $source;

        $map = ['device' => 'fingerprint', 'finger' => 'fingerprint', 'biometric' => 'fingerprint', 'machine' => 'fingerprint', 'import' => 'fingerprint'];

        return isset($map[$source]) ? $map[$source] : 'system';
    }

    private function isWorkingDay(?HrShift $shift, string $dateStr): bool
    {
        if (!$shift) return true;

        return match (Carbon::parse($dateStr)->dayOfWeek) {
            0 => (bool)$shift->sun,
            1 => (bool)$shift->mon,
            2 => (bool)$shift->tue,
            3 => (bool)$shift->wed,
            4 => (bool)$shift->thu,
            5 => (bool)$shift->fri,
            6 => (bool)$shift->sat,
            default => true,
        };
    }

    private function getEmployeeShiftForDate(int $employeeId, int $branchId, string $dateStr): ?HrShift
    {
        $q = HrEmployeeShift::with('shift')
            ->where('employee_id', $employeeId)
            ->where('branch_id', $branchId)
            ->where('status', 1)
            ->where(fn($w) => $w->whereNull('start_date')->orWhere('start_date', '<=', $dateStr))
            ->where(fn($w) => $w->whereNull('end_date')->orWhere('end_date', '>=', $dateStr))
            ->orderByDesc('id')
            ->first();

        if ($q && $q->shift) return $q->shift;

        return HrShift::where('status', 1)->orderBy('id')->first();
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
