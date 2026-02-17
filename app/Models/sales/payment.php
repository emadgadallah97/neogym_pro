<?php

namespace App\Models\sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'member_id',
        'member_subscription_id',
        'amount',
        'payment_method',
        'status',
        'paid_at',
        'reference',
        'notes',
        'user_add',
        'source',

    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(\App\Models\members\Member::class, 'member_id');
    }

    public function memberSubscription()
    {
        return $this->belongsTo(MemberSubscription::class, 'member_subscription_id');
    }
}
