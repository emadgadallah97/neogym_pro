<?php

namespace App\Models\coupons_offers;

use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $table = 'coupon_usages';

    protected $fillable = [
        'coupon_id',
        'member_id',
        'applied_to_type',
        'applied_to_id',
        'amount_before',
        'discount_amount',
        'amount_after',
        'used_at',
    ];

    protected $casts = [
        'amount_before' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'amount_after' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function appliedTo()
    {
        return $this->morphTo(null, 'applied_to_type', 'applied_to_id');
    }
}
