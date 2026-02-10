<?php

namespace App\Models\attendances;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class attendance extends Model
{
    use SoftDeletes;

    protected $table = 'attendances';

    protected $fillable = [
        'branch_id',
        'member_id',
        'attendance_date',
        'attendance_time',
        'day_key',
        'member_subscription_id',
        'pt_addon_id',
        'is_base_deducted',
        'is_pt_deducted',
        'base_sessions_before',
        'base_sessions_after',
        'pt_sessions_before',
        'pt_sessions_after',
        'checkin_method',
        'recorded_by',
        'device_id',
        'gate_id',
        'check_out_at',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'pt_refunded_at',
        'pt_refunded_by',
        'notes',
        'user_add',
        'user_update',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'is_base_deducted' => 'boolean',
        'is_pt_deducted' => 'boolean',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
        'check_out_at' => 'datetime',
        'pt_refunded_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function member()
    {
        return $this->belongsTo(\App\Models\members\Member::class, 'member_id');
    }

    public function subscription()
    {
        return $this->belongsTo(\App\Models\sales\MemberSubscription::class, 'member_subscription_id');
    }

    public function ptAddon()
    {
        return $this->belongsTo(\App\Models\sales\MemberSubscriptionPtAddon::class, 'pt_addon_id');
    }

    public function recorder()
    {
        return $this->belongsTo(\App\User::class, 'recorded_by');
    }

    public function guests()
    {
        return $this->hasMany(attendanceguest::class, 'attendance_id');
    }
}
