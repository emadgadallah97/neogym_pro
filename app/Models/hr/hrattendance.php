<?php

namespace App\Models\hr;

use Illuminate\Database\Eloquent\Model;

class HrAttendance extends Model
{
    protected $table = 'hr_attendance';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'device_id',
        'date',
        'check_in',
        'check_out',
        'total_hours',
        'status',
        'source',
        'notes',
        'user_add',
    ];

    protected $casts = [
        'date'      => 'date',
        'check_in'  => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\employee\employee::class, 'employee_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function device()
    {
        return $this->belongsTo(HrDevice::class, 'device_id');
    }

    public function logs()
    {
        return $this->hasMany(HrAttendanceLog::class, 'attendance_id');
    }

    public function calculateTotalHours(): float
    {
        if ($this->check_in && $this->check_out) {
            return round(
                (strtotime($this->check_out) - strtotime($this->check_in)) / 3600,
                2
            );
        }
        return 0;
    }
}
