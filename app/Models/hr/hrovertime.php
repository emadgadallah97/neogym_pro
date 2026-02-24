<?php

namespace App\Models\hr;

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

    // سعر الساعة التلقائي: الراتب ÷ 26 يوم ÷ 8 ساعات
    public static function calcHourRate(float $baseSalary): float
    {
        return round($baseSalary / 26 / 8, 2);
    }
}
