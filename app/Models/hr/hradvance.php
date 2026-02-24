<?php

namespace App\Models\hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrAdvance extends Model
{
    use SoftDeletes;

    protected $table = 'hr_advances';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'total_amount',
        'monthly_installment',
        'installments_count',
        'paid_amount',
        'remaining_amount',
        'request_date',
        'start_month',
        'status',
        'notes',
        'user_add',
    ];

    protected $casts = [
        'request_date' => 'date',
        'start_month'  => 'date',
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

    public function installments()
    {
        return $this->hasMany(HrAdvanceInstallment::class, 'advance_id');
    }

    public function pendingInstallments()
    {
        return $this->installments()->where('is_paid', false);
    }
}
