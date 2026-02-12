<?php

namespace App\Models\accounting;

use App\Models\employee\employee as Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionSettlement extends Model
{
    use SoftDeletes;

    protected $table = 'commission_settlements';

    protected $fillable = [
        'date_from',
        'date_to',
        'sales_employee_id',
        'status',
        'total_commission_amount',
        'total_excluded_commission_amount',
        'total_all_commission_amount',
        'items_count',
        'excluded_items_count',
        'all_items_count',
        'paid_at',
        'paid_by',
        'notes',
        'user_add',
        'user_update',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'paid_at' => 'datetime',
        'total_commission_amount' => 'decimal:2',
        'total_excluded_commission_amount' => 'decimal:2',
        'total_all_commission_amount' => 'decimal:2',
        'items_count' => 'integer',
        'excluded_items_count' => 'integer',
        'all_items_count' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(CommissionSettlementItem::class, 'commission_settlement_id');
    }

    public function salesEmployee()
    {
        return $this->belongsTo(Employee::class, 'sales_employee_id');
    }

    public function paidByUser()
    {
        return $this->belongsTo(\App\User::class, 'paid_by');
    }
}
