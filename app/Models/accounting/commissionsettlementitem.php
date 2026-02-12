<?php

namespace App\Models\accounting;

use Illuminate\Database\Eloquent\Model;

class CommissionSettlementItem extends Model
{
    protected $table = 'commission_settlement_items';

    protected $fillable = [
        'commission_settlement_id',
        'member_subscription_id',
        'member_id',
        'branch_id',
        'sales_employee_id',
        'commission_base_amount',
        'commission_value_type',
        'commission_value',
        'commission_amount',
        'subscription_created_at',
        'is_excluded',
        'exclude_reason',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'commission_base_amount' => 'decimal:2',
        'commission_value' => 'decimal:2',
        'is_excluded' => 'boolean',
        'subscription_created_at' => 'datetime',
    ];

    public function settlement()
    {
        return $this->belongsTo(CommissionSettlement::class, 'commission_settlement_id');
    }

    public function memberSubscription()
    {
        return $this->belongsTo(\App\Models\sales\MemberSubscription::class, 'member_subscription_id');
    }
}
