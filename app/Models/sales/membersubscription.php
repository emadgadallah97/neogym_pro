<?php

namespace App\Models\sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberSubscription extends Model
{
    use SoftDeletes;

    protected $table = 'member_subscriptions';

    protected $fillable = [
        'member_id',
        'branch_id',
        'subscriptions_plan_id',
        'subscriptions_type_id',
        'plan_code',
        'plan_name',
        'duration_days',
        'sessions_count',
        'with_trainer',
        'main_trainer_id',
        'sessions_included',
        'sessions_remaining',
        'start_date',
        'end_date',
        'status',
        'allow_all_branches',
        'source',
        'price_plan',
        'price_pt_addons',
        'discount_offer_amount',
        'discount_coupon_amount',
        'total_discount',
        'total_amount',
        'offer_id',
        'coupon_id',
        'sales_employee_id',
        'commission_base_amount',
        'commission_value_type',
        'commission_value',
        'commission_amount',
        'user_add',
        'user_update',
        'notes',
    ];

    protected $casts = [
        'plan_name'              => 'array',
        'with_trainer'           => 'boolean',
        'allow_all_branches'     => 'boolean',
        'duration_days'          => 'integer',
        'sessions_count'         => 'integer',
        'sessions_included'      => 'integer',
        'sessions_remaining'     => 'integer',
        'price_plan'             => 'decimal:2',
        'price_pt_addons'        => 'decimal:2',
        'discount_offer_amount'  => 'decimal:2',
        'discount_coupon_amount' => 'decimal:2',
        'total_discount'         => 'decimal:2',
        'total_amount'           => 'decimal:2',
        'commission_base_amount' => 'decimal:2',
        'commission_value'       => 'decimal:2',
        'commission_amount'      => 'decimal:2',
        'start_date'             => 'date',
        'end_date'               => 'date',
    ];

    // علاقات
    public function member()
    {
        return $this->belongsTo(\App\Models\members\Member::class, 'member_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function plan()
    {
        return $this->belongsTo(\App\Models\subscriptions\subscriptions_plan::class, 'subscriptions_plan_id');
    }

    public function type()
    {
        return $this->belongsTo(\App\Models\subscriptions\subscriptions_type::class, 'subscriptions_type_id');
    }

    public function mainTrainer()
    {
        return $this->belongsTo(\App\Models\employee\Employee::class, 'main_trainer_id');
    }

    public function salesEmployee()
    {
        return $this->belongsTo(\App\Models\employee\Employee::class, 'sales_employee_id');
    }

    public function offer()
    {
        return $this->belongsTo(\App\Models\coupons_offers\Offer::class, 'offer_id');
    }

    public function coupon()
    {
        return $this->belongsTo(\App\Models\coupons_offers\Coupon::class, 'coupon_id');
    }

    public function ptAddons()
    {
        return $this->hasMany(MemberSubscriptionPtAddon::class, 'member_subscription_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'member_subscription_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'member_subscription_id');
    }
}
