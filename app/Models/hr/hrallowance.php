<?php

namespace App\Models\hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrAllowance extends Model
{
    use SoftDeletes;

    protected $table = 'hr_allowances';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'type',
        'reason',
        'amount',
        'date',
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

    // أنواع الإضافات للعرض في الـ View
    public static function types(): array
    {
        return [
            'bonus'          => 'مكافأة',
            'incentive'      => 'حافز',
            'transportation' => 'بدل مواصلات',
            'housing'        => 'بدل سكن',
            'meal'           => 'بدل وجبة',
            'other'          => 'أخرى',
        ];
    }

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
}
