<?php

namespace App\Models\hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrPayroll extends Model
{
    use SoftDeletes;

    protected $table = 'hr_payrolls';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'month',
        'base_salary',
        'overtime_amount',
        'allowances_amount',
        'advances_deduction',
        'deductions_amount',
        'gross_salary',
        'net_salary',
        'payment_date',
        'payment_method',
        'salary_transfer_details',
        'payment_reference',
        'status',
        'notes',
        'user_add',
    ];

    protected $casts = [
        'month'        => 'date',
        'payment_date' => 'date',
    ];

    protected $dates = ['deleted_at'];

    // ── Relationships ──

    public function employee()
    {
        return $this->belongsTo(\App\Models\employee\employee::class, 'employee_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function advanceInstallments()
    {
        return $this->hasMany(HrAdvanceInstallment::class, 'payroll_id');
    }

    public function deductions()
    {
        return $this->hasMany(HrDeduction::class, 'payroll_id');
    }

    public function overtimes()
    {
        return $this->hasMany(HrOvertime::class, 'payroll_id');
    }

    public function allowances()
    {
        return $this->hasMany(HrAllowance::class, 'payroll_id');
    }

    // ── حساب الراتب الصافي ──
    public function calculateSalary(): void
    {
        $this->gross_salary = $this->base_salary
            + $this->overtime_amount
            + $this->allowances_amount;

        $this->net_salary = $this->gross_salary
            - $this->advances_deduction
            - $this->deductions_amount;
    }
}
