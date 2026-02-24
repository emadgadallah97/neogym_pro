<?php

namespace App\Models\hr;

use Illuminate\Database\Eloquent\Model;

class HrAttendanceLog extends Model
{
    protected $table = 'hr_attendance_logs';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'device_id',
        'attendance_id',
        'punch_time',
        'punch_type',
        'is_processed',
        'raw_data',
    ];

    protected $casts = [
        'punch_time'   => 'datetime',
        'is_processed' => 'boolean',
        'raw_data'     => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\employee\employee::class, 'employee_id');
    }

    public function device()
    {
        return $this->belongsTo(HrDevice::class, 'device_id');
    }

    public function attendance()
    {
        return $this->belongsTo(HrAttendance::class, 'attendance_id');
    }

    // ── Scopes ──

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('punch_time', $date);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
