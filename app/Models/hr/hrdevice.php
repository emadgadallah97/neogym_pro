<?php

namespace App\Models\hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrDevice extends Model
{
    use SoftDeletes;

    protected $table = 'hr_devices';
    public $timestamps = true;

    protected $fillable = [
        'branch_id',
        'name',
        'serial_number',
        'ip_address',
        'status',
        'notes',
        'user_add',
    ];

    protected $dates = ['deleted_at'];

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(HrAttendanceLog::class, 'device_id');
    }

    public function attendance()
    {
        return $this->hasMany(HrAttendance::class, 'device_id');
    }
}
