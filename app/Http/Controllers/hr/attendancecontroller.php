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
    public function index(Request $request)
    {
        $branches = Branch::where('status', 1)->orderBy('id')->get();

        // Default: daily
        $mode       = $request->get('mode', 'daily'); // monthly | daily
        $branchId   = (int)($request->get('branch_id', Auth::user()->branch_id ?? 0));
        $employeeId = (int)($request->get('employee_id', 0));

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
            'branches',
            'employees',
            'rows',
            'mode',
            'branchId',
            'employeeId',
            'month',
            'date'
        ));
    }

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

    // ─────────────────────────────────────────
    // Manual CRUD (AJAX)
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

        $employee = employee::findOrFail($data['employee_id']);

        if (!$this->isEmployeePrimaryInBranch($employee->id, (int)$data['branch_id'])) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        $shift = $this->getEmployeeShiftForDate($employee->id, (int)$data['branch_id'], $data['date']);

        $checkInDT  = $data['check_in'] ? Carbon::parse($data['date'] . ' ' . $data['check_in']) : null;
        $checkOutDT = $data['check_out'] ? Carbon::parse($data['date'] . ' ' . $data['check_out']) : null;

        $attendance = HrAttendance::where('employee_id', $employee->id)
            ->whereDate('date', $data['date'])
            ->first();

        if (!$attendance) {
            $attendance = new HrAttendance();
            $attendance->employee_id = $employee->id;
            $attendance->branch_id   = (int)$data['branch_id'];
            $attendance->date        = $data['date'];
            $attendance->source      = 'manual';
            $attendance->user_add    = Auth::id();
        }

        $attendance->check_in  = $checkInDT;
        $attendance->check_out = $checkOutDT;
        $attendance->notes     = $data['notes'] ?? null;

        $attendance->total_hours = $this->calcHours($attendance->check_in, $attendance->check_out);
        $attendance->status = $this->normalizeAttendanceStatus(
            $this->calcStatus($shift, $attendance->date, $attendance->check_in, $attendance->check_out, (float)$attendance->total_hours)
        );

        // ✅ source enum: fingerprint/manual/system
        $attendance->source = $this->normalizeAttendanceSource($attendance->source ?: 'manual');

        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => trans('hr.attendance_saved_success'),
            'data'    => $this->attendanceRowDto($attendance, $employee, $shift, false),
        ]);
    }

    public function show($id)
    {
        $att = HrAttendance::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id'          => $att->id,
                'employee_id' => $att->employee_id,
                'branch_id'   => $att->branch_id,
                'date'        => Carbon::parse($att->date)->toDateString(),
                'check_in'    => $att->check_in ? Carbon::parse($att->check_in)->format('H:i') : null,
                'check_out'   => $att->check_out ? Carbon::parse($att->check_out)->format('H:i') : null,
                'notes'       => $att->notes,
            ]
        ]);
    }

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

        $employee = employee::findOrFail($data['employee_id']);

        if (!$this->isEmployeePrimaryInBranch($employee->id, (int)$data['branch_id'])) {
            return response()->json(['success' => false, 'message' => trans('hr.employee_not_in_branch')], 422);
        }

        $shift = $this->getEmployeeShiftForDate($employee->id, (int)$data['branch_id'], $data['date']);

        $checkInDT  = $data['check_in'] ? Carbon::parse($data['date'] . ' ' . $data['check_in']) : null;
        $checkOutDT = $data['check_out'] ? Carbon::parse($data['date'] . ' ' . $data['check_out']) : null;

        $att->employee_id = $employee->id;
        $att->branch_id   = (int)$data['branch_id'];
        $att->date        = $data['date'];
        $att->check_in    = $checkInDT;
        $att->check_out   = $checkOutDT;
        $att->notes       = $data['notes'] ?? null;

        if (!$att->source) $att->source = 'manual';

        $att->total_hours = $this->calcHours($att->check_in, $att->check_out);
        $att->status = $this->normalizeAttendanceStatus(
            $this->calcStatus($shift, $att->date, $att->check_in, $att->check_out, (float)$att->total_hours)
        );

        // ✅ source enum
        $att->source = $this->normalizeAttendanceSource($att->source);

        $att->save();

        return response()->json([
            'success' => true,
            'message' => trans('hr.attendance_updated_success'),
            'data'    => $this->attendanceRowDto($att, $employee, $shift, false),
        ]);
    }

    public function destroy($id)
    {
        $att = HrAttendance::findOrFail($id);

        $employee = employee::find($att->employee_id);
        $shift    = $this->getEmployeeShiftForDate($att->employee_id, $att->branch_id, Carbon::parse($att->date)->toDateString());

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
            ]
        ]);
    }

    // ─────────────────────────────────────────
    // Processing Logs
    // ─────────────────────────────────────────
    public function processIndex(Request $request)
    {
        $branches = Branch::where('status', 1)->orderBy('id')->get();
        $devices  = HrDevice::orderBy('id')->get();

        $branchId = (int)($request->get('branch_id', Auth::user()->branch_id ?? 0));
        $deviceId = (int)$request->get('device_id', 0);
        $date     = $request->get('date', Carbon::today()->toDateString());

        $logsQ = HrAttendanceLog::with(['employee', 'device'])
            ->where('is_processed', false)
            ->whereDate('punch_time', $date);

        if ($deviceId > 0) $logsQ->where('device_id', $deviceId);

        if ($branchId > 0) {
            $employeeIds = $this->getEmployeesByPrimaryBranch($branchId)->pluck('id')->toArray();
            $logsQ->whereIn('employee_id', $employeeIds);
        }

        $logs = $logsQ->orderBy('punch_time')->get();

        return view('hr.attendance.process', compact('branches', 'devices', 'branchId', 'deviceId', 'date', 'logs'));
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

        $employees = $this->getEmployeesByPrimaryBranch($branchId);
        $employeeIds = $employees->pluck('id')->toArray();

        $logsQ = HrAttendanceLog::unprocessed()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('punch_time', $dateStr);

        if ($deviceId > 0) $logsQ->where('device_id', $deviceId);

        $logs = $logsQ->orderBy('punch_time')->get();

        $groups = $logs->groupBy(function ($l) {
            return $l->employee_id . '|' . Carbon::parse($l->punch_time)->toDateString();
        });

        $created = 0;
        $updated = 0;
        $processedLogs = 0;

        DB::beginTransaction();
        try {
            foreach ($groups as $key => $items) {
                [$empId, $d] = explode('|', $key);
                $empId = (int)$empId;

                $employee = $employees->firstWhere('id', $empId);
                if (!$employee) continue;

                $shift = $this->getEmployeeShiftForDate($empId, $branchId, $d);

                $first = $items->first();
                $last  = $items->last();

                $checkIn  = Carbon::parse($first->punch_time);
                $checkOut = Carbon::parse($last->punch_time);
                if ($items->count() === 1) $checkOut = null;

                $attendance = HrAttendance::where('employee_id', $empId)
                    ->whereDate('date', $d)
                    ->first();

                if (!$attendance) {
                    $attendance = new HrAttendance();
                    $attendance->employee_id = $empId;
                    $attendance->branch_id   = $branchId;
                    $attendance->date        = $d;

                    // ✅ المصدر من الجهاز = fingerprint
                    $attendance->source      = 'fingerprint';

                    $attendance->user_add    = Auth::id();
                    $created++;
                } else {
                    $updated++;
                }

                $attendance->device_id   = $deviceId > 0 ? $deviceId : ($first->device_id ?? null);
                $attendance->check_in    = $checkIn;
                $attendance->check_out   = $checkOut;
                $attendance->total_hours = $this->calcHours($attendance->check_in, $attendance->check_out);

                $attendance->status = $this->normalizeAttendanceStatus(
                    $this->calcStatus($shift, $attendance->date, $attendance->check_in, $attendance->check_out, (float)$attendance->total_hours)
                );

                // ✅ normalize source (في حال قديم/فارغ)
                $attendance->source = $this->normalizeAttendanceSource($attendance->source ?: 'fingerprint');

                $attendance->save();

                foreach ($items as $log) {
                    $log->attendance_id = $attendance->id;
                    $log->is_processed  = true;
                    $log->save();
                    $processedLogs++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('attendance.processRun error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            $msg = trans('hr.error_occurred');
            if (config('app.debug')) {
                $msg = $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine();
            }

            return response()->json(['success' => false, 'message' => $msg], 500);
        }

        return response()->json([
            'success' => true,
            'message' => trans('hr.logs_processed_success'),
            'data'    => ['created' => $created, 'updated' => $updated, 'logs' => $processedLogs]
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

            $att = HrAttendance::where('employee_id', $emp->id)->whereDate('date', $date)->first();

            $rows[] = $att
                ? $this->attendanceRowDto($att, $emp, $shift, false)
                : $this->virtualAbsentRowDto($emp, $branchId, $date, $shift);
        }

        return $rows;
    }

    private function buildMonthlyRows(int $branchId, $employees, string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $period = CarbonPeriod::create($start, $end);

        $att = HrAttendance::whereIn('employee_id', $employees->pluck('id')->toArray())
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($a) => $a->employee_id . '|' . Carbon::parse($a->date)->toDateString());

        $rows = [];

        foreach ($employees as $emp) {
            foreach ($period as $day) {
                $dateStr = $day->toDateString();
                $shift = $this->getEmployeeShiftForDate($emp->id, $branchId, $dateStr);
                if (!$this->isWorkingDay($shift, $dateStr)) continue;

                $key = $emp->id . '|' . $dateStr;

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
            'is_virtual'     => $isVirtual ? 1 : 0,
            'attendance_id'  => $a->id,
            'row_dom_id'     => 'row-att-' . $a->id,
            'employee_id'    => $emp->id,
            'employee_name'  => $emp->full_name ?? $emp->getFullNameAttribute(),
            'employee_code'  => $emp->code,
            'branch_id'      => $a->branch_id,
            'date'           => $dateStr,
            'shift_name'     => $shift?->name ?? trans('hr.no_shift'),
            'check_in'       => $a->check_in ? Carbon::parse($a->check_in)->format('H:i') : '—',
            'check_out'      => $a->check_out ? Carbon::parse($a->check_out)->format('H:i') : '—',
            'total_hours'    => $a->total_hours ? number_format((float)$a->total_hours, 2) : '0.00',
            'status'         => $a->status ?? 'present',
            'source'         => $a->source ?? 'system',
            'notes'          => $a->notes ?? '',
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

    private function calcHours($checkIn, $checkOut): float
    {
        if (!$checkIn || !$checkOut) return 0;
        $in  = Carbon::parse($checkIn);
        $out = Carbon::parse($checkOut);
        if ($out->lessThanOrEqualTo($in)) return 0;
        return round($out->diffInMinutes($in) / 60, 2);
    }

    private function calcStatus(?HrShift $shift, $date, $checkIn, $checkOut, float $hours): string
    {
        $dateStr = Carbon::parse($date)->toDateString();

        if (!$shift) return $checkIn ? 'present' : 'absent';
        if (!$this->isWorkingDay($shift, $dateStr)) return 'absent';
        if (!$checkIn) return 'absent';

        $scheduledIn = Carbon::parse($dateStr . ' ' . $shift->start_time);
        $graceIn     = (clone $scheduledIn)->addMinutes((int)$shift->grace_minutes);

        $status = 'present';

        if (Carbon::parse($checkIn)->greaterThan($graceIn)) {
            $status = 'late';
        }

        if ($checkIn && !$checkOut) {
            $status = 'half_day';
        }

        if ($hours > 0 && $hours < (float)$shift->min_full_hours) {
            $status = 'half_day';
        }

        return $status;
    }

    private function normalizeAttendanceStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $allowed = ['present','absent','late','half_day','leave'];

        if (in_array($status, $allowed, true)) return $status;

        $map = [
            'halfday'  => 'half_day',
            'half-day' => 'half_day',
            'half day' => 'half_day',
        ];

        if (isset($map[$status])) return $map[$status];

        return 'present';
    }

    // ✅ source enum = fingerprint/manual/system
    private function normalizeAttendanceSource(string $source): string
    {
        $source = strtolower(trim($source));
        $allowed = ['fingerprint','manual','system'];

        if (in_array($source, $allowed, true)) return $source;

        $map = [
            'device'      => 'fingerprint',
            'finger'      => 'fingerprint',
            'biometric'   => 'fingerprint',
            'machine'     => 'fingerprint',
            'import'      => 'fingerprint',
        ];

        if (isset($map[$source]) && in_array($map[$source], $allowed, true)) {
            return $map[$source];
        }

        return 'system';
    }

    private function isWorkingDay(?HrShift $shift, string $dateStr): bool
    {
        if (!$shift) return true;

        $dow = Carbon::parse($dateStr)->dayOfWeek;

        return match ($dow) {
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
            ->where(function ($w) use ($dateStr) {
                $w->whereNull('start_date')->orWhere('start_date', '<=', $dateStr);
            })
            ->where(function ($w) use ($dateStr) {
                $w->whereNull('end_date')->orWhere('end_date', '>=', $dateStr);
            })
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
