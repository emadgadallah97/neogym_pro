<?php

namespace App\Models\sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberSubscriptionPtAddon extends Model
{
    use SoftDeletes;

    protected $table = 'member_subscription_pt_addons';

    protected $fillable = [
        'member_subscription_id',
        'trainer_id',
        'session_price',
        'sessions_count',
        'sessions_remaining',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'session_price'      => 'decimal:2',
        'sessions_count'     => 'integer',
        'sessions_remaining' => 'integer',
        'total_amount'       => 'decimal:2',
    ];

    public function memberSubscription()
    {
        return $this->belongsTo(MemberSubscription::class, 'member_subscription_id');
    }

    public function trainer()
    {
        return $this->belongsTo(\App\Models\employee\Employee::class, 'trainer_id');
    }
}
