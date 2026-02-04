<?php

namespace App\Models\coupons_offers;

use Illuminate\Database\Eloquent\Model;

class CouponDuration extends Model
{
    protected $table = 'coupon_durations';

    protected $fillable = [
        'coupon_id',
        'duration_value',
        'duration_unit',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}
