<?php

namespace App\Models\hr;

use Illuminate\Database\Eloquent\Model;

class HrAdvanceInstallment extends Model
{
    protected $table = 'hr_advance_installments';
    public $timestamps = true;

    protected $fillable = [
        'advance_id',
        'employee_id',
        'month',
        'amount',
        'is_paid',
        'payroll_id',
        'paid_date',
    ];

    protected $casts = [
        'month'     => 'date',
        'paid_date' => 'date',
        'is_paid'   => 'boolean',
    ];

    public function advance()
    {
        return $this->belongsTo(HrAdvance::class, 'advance_id');
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\employee\employee::class, 'employee_id');
    }

    public function payroll()
    {
        return $this->belongsTo(HrPayroll::class, 'payroll_id');
    }
}
