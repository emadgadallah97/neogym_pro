<?php

namespace App\Models\hr;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeShift extends Model
{
    protected $table = 'hr_employee_shifts';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'shift_id',
        'start_date',
        'end_date',
        'status',
        'user_add',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'status'     => 'boolean',
    ];

    public function shift()
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\employee\employee::class, 'employee_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }
}
