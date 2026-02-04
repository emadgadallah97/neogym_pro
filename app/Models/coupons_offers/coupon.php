<?php

namespace App\Models\coupons_offers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Coupon extends Model
{
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'coupons';

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'code',
        'name',
        'description',
        'applies_to',
        'discount_type',
        'discount_value',
        'min_amount',
        'max_discount',
        'max_uses_total',
        'max_uses_per_member',
        'member_id',
        'start_at',
        'end_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'discount_value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function plans()
    {
        return $this->belongsToMany(
            \App\Models\subscriptions\subscriptions_plan::class,
            'coupon_subscriptions_plans',
            'coupon_id',
            'subscriptions_plan_id'
        )->withTimestamps();
    }
 public function branches()
    {
        return $this->belongsToMany(
            \App\Models\general\Branch::class,
            'coupon_branches',
            'coupon_id',
            'branch_id'
        )->withTimestamps();
    }
    public function types()
    {
        return $this->belongsToMany(
            \App\Models\subscriptions\subscriptions_type::class,
            'coupon_subscriptions_types',
            'coupon_id',
            'subscriptions_type_id'
        )->withTimestamps();
    }

    public function durations()
    {
        return $this->hasMany(CouponDuration::class, 'coupon_id');
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class, 'coupon_id');
    }

    public function scopeValidNow($q)
    {
        $now = Carbon::now();

        return $q->where('status', 'active')
            ->where(function ($x) use ($now) {
                $x->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($x) use ($now) {
                $x->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->end_at) {
            return false;
        }

        return $this->end_at->lt(Carbon::now());
    }

    public function usedCountTotal(): int
    {
        return (int) $this->usages()->count();
    }

    public function usedCountForMember(?int $memberId): int
    {
        if (!$memberId) {
            return 0;
        }

        return (int) $this->usages()->where('member_id', $memberId)->count();
    }
}
