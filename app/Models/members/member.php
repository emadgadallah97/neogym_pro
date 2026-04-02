<?php

namespace App\Models\members;

use App\Models\Scopes\ExcludeProspectsScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Member extends Model
{
    use SoftDeletes;

    protected $table = 'members';
    public $timestamps = true;

    protected $fillable = [
        'member_code',
        'device_user_id',
        'branch_id',
        'referral_source_id',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'phone',
        'phone2',
        'whatsapp',
        'email',
        'national_id',
        'address',
        'id_government',
        'id_city',
        'id_area',
        'join_date',
        'status',
        'type',
        'freeze_from',
        'freeze_to',
        'height',
        'weight',
        'medical_conditions',
        'allergies',
        'notes',
        'photo',
        'emergency_contacts',
        'user_add',
        'user_update',
    ];

    protected $casts = [
        'birth_date'         => 'date',
        'join_date'          => 'date',
        'freeze_from'        => 'date',
        'freeze_to'          => 'date',
        'height'             => 'decimal:2',
        'weight'             => 'decimal:2',
        'emergency_contacts' => 'array',
    ];

    // ══════════════════════════════════════════════════════
    //  Global Scope — يستثني المحتملين من كل النظام تلقائياً
    // ══════════════════════════════════════════════════════

    protected static function booted(): void
    {
        static::addGlobalScope(new ExcludeProspectsScope());
    }

    // ══════════════════════════════════════════════════════
    //  Local Scopes
    // ══════════════════════════════════════════════════════

    public function scopeProspects(Builder $query): Builder
    {
        return $query->withoutGlobalScope(ExcludeProspectsScope::class)
                     ->where('type', 'prospect');
    }

    public function scopeWithProspects(Builder $query): Builder
    {
        return $query->withoutGlobalScope(ExcludeProspectsScope::class);
    }

    // ══════════════════════════════════════════════════════
    //  Convert Prospect → Member
    // ══════════════════════════════════════════════════════

    public function convertToMember(): bool
    {
        return $this->update([
            'type'   => 'member',
            'status' => 'active',
        ]);
    }

    public function isProspect(): bool
    {
        return $this->type === 'prospect';
    }

    public function isMember(): bool
    {
        return $this->type === 'member';
    }

    // ══════════════════════════════════════════════════════
    //  Relationships
    // ══════════════════════════════════════════════════════

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function referralSource()
    {
        return $this->belongsTo(\App\Models\ReferralSource::class, 'referral_source_id');
    }

    public function government()
    {
        return $this->belongsTo(\App\models\government::class, 'id_government');
    }

    public function city()
    {
        return $this->belongsTo(\App\models\City::class, 'id_city');
    }

    public function area()
    {
        return $this->belongsTo(\App\models\area::class, 'id_area');
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\sales\MemberSubscription::class, 'member_id');
    }

    public function attendances()
    {
        return $this->hasMany(\App\Models\attendances\attendance::class, 'member_id');
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\sales\Invoice::class, 'member_id');
    }

    // ✅ علاقة المتابعات — CRM
    public function followups()
    {
        return $this->hasMany(\App\Models\crm\CrmFollowup::class, 'member_id');
    }

    // ══════════════════════════════════════════════════════
    //  Accessors
    // ══════════════════════════════════════════════════════

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getIsFrozenNowAttribute(): bool
    {
        if (($this->status ?? '') !== 'frozen') {
            return false;
        }

        if (empty($this->freeze_from) || empty($this->freeze_to)) {
            return false;
        }

        $today = Carbon::today();
        return $today->between($this->freeze_from, $this->freeze_to);
    }

    public function getPublicPhotoUrlAttribute(): ?string
    {
        return !empty($this->photo) ? url($this->photo) : null;
    }
}
