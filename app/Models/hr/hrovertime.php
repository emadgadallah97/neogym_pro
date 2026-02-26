<?php

namespace App\Models\hr;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrOvertime extends Model
{
    use SoftDeletes;

    protected $table = 'hr_overtime';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'date',
        'hours',
        'hour_rate',
        'total_amount',
        'applied_month',
        'status',
        'payroll_id',
        'notes',
        'user_add',
        'attendance_id',
        'source',
    ];

    protected $casts = [
        'date'          => 'date',
        'applied_month' => 'date',
    ];

    protected $dates = ['deleted_at'];

    public function employee()
    {
        return $this->belongsTo(\App\Models\employee\employee::class, 'employee_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function payroll()
    {
        return $this->belongsTo(HrPayroll::class, 'payroll_id');
    }

    /**
     * حساب مدة الوردية بالساعات من start_time/end_time (يدعم الورديات الليلية).
     */
    public static function calcShiftHours(string $date, string $startTime, string $endTime): float
    {
        try {
            $start = Carbon::parse($date . ' ' . $startTime);
            $end   = Carbon::parse($date . ' ' . $endTime);

            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay(); // night shift
            }

            $minutes = $start->diffInMinutes($end);
            $hours = $minutes / 60;

            return round(max(0.0, $hours), 2);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * جلب عدد ساعات وردية الموظف في تاريخ معين من HrEmployeeShift -> shift.
     * fallbackHours افتراضي 8 لو لا توجد وردية.
     */
    public static function getEmployeeShiftHours(int $employeeId, int $branchId, string $date, float $fallbackHours = 8.0): float
    {
        try {
            $d = Carbon::parse($date)->toDateString();

            $es = HrEmployeeShift::with('shift')
                ->where('employee_id', $employeeId)
                ->where('branch_id', $branchId)
                ->where('status', 1)
                ->where(function ($q) use ($d) {
                    $q->whereNull('start_date')->orWhereDate('start_date', '<=', $d);
                })
                ->where(function ($q) use ($d) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $d);
                })
                ->orderByDesc('id')
                ->first();

            $shift = $es?->shift;
            if (!$shift || !$shift->start_time || !$shift->end_time) {
                return round($fallbackHours, 2);
            }

            $hours = self::calcShiftHours($d, (string)$shift->start_time, (string)$shift->end_time);
            if ($hours <= 0) return round($fallbackHours, 2);

            return round($hours, 2);
        } catch (\Throwable $e) {
            return round($fallbackHours, 2);
        }
    }

    /**
     * سعر الساعة التلقائي:
     * الراتب ÷ 26 يوم ÷ (ساعات الوردية) × multiplier
     * multiplier عندك = 2 (حسب معادلتك الحالية).
     */
    public static function calcHourRate(float $baseSalary, float $shiftHours = 8.0, float $multiplier = 2.0): float
    {
        $shiftHours = (float)$shiftHours;
        if ($shiftHours <= 0) $shiftHours = 8.0;

        return round(($baseSalary / 26 / $shiftHours) * $multiplier, 2);
    }
}
