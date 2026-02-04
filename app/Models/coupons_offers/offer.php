<?php

namespace App\Models\coupons_offers;

use Carbon\Carbon;
use subscriptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'offers';

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description',
        'applies_to',
        'discount_type',
        'discount_value',
        'min_amount',
        'max_discount',
        'start_at',
        'end_at',
        'status',
        'priority',
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
            'offer_subscriptions_plans',
            'offer_id',
            'subscriptions_plan_id'
        )->withTimestamps();
    }
  public function branches()
    {
        return $this->belongsToMany(
            \App\Models\general\Branch::class,
            'offer_branches',
            'offer_id',
            'branch_id'
        )->withTimestamps();
    }

    public function types()
    {
        return $this->belongsToMany(
            \App\Models\subscriptions\subscriptions_type::class,
            'offer_subscriptions_types',
            'offer_id',
            'subscriptions_type_id'
        )->withTimestamps();
    }

    public function durations()
    {
        return $this->hasMany(OfferDuration::class, 'offer_id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
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
}
