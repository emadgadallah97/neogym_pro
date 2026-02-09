<?php

namespace App\Models\sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'member_id',
        'branch_id',
        'member_subscription_id',
        'currency_id',
        'subtotal',
        'discount_total',
        'total',
        'status',
        'issued_at',
        'paid_at',
        'notes',
        'user_add',
    ];

    protected $casts = [
        'subtotal'       => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total'          => 'decimal:2',
        'issued_at'      => 'datetime',
        'paid_at'        => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(\App\Models\members\Member::class, 'member_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function memberSubscription()
    {
        return $this->belongsTo(MemberSubscription::class, 'member_subscription_id');
    }

    public function currency()
    {
        return $this->belongsTo(\App\models\currencies::class, 'currency_id');
    }
}
