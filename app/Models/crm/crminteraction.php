<?php
// app/Models/crm/CrmInteraction.php

namespace App\Models\crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class crminteraction extends Model
{
    use SoftDeletes;

    protected $table = 'crm_interactions';

    protected $fillable = [
        'member_id',
        'followup_id',
        'channel',
        'direction',
        'notes',
        'result',
        'interacted_at',
        'created_by',
    ];

    protected $casts = [
        'interacted_at' => 'datetime',
    ];

    // ── Labels ───────────────────────────────────────────────────────

    public static function channelLabels(): array
    {
        return [
            'call'     => 'مكالمة هاتفية',
            'whatsapp' => 'واتساب',
            'visit'    => 'زيارة',
            'email'    => 'بريد إلكتروني',
            'sms'      => 'رسالة نصية',
        ];
    }

    public static function channelIcons(): array
    {
        return [
            'call'     => 'fas fa-phone',
            'whatsapp' => 'fab fa-whatsapp',
            'visit'    => 'fas fa-walking',
            'email'    => 'fas fa-envelope',
            'sms'      => 'fas fa-sms',
        ];
    }

    public static function resultLabels(): array
    {
        return [
            'answered'       => 'تم الرد',
            'no_answer'      => 'لم يرد',
            'interested'     => 'مهتم',
            'not_interested' => 'غير مهتم',
            'callback'       => 'طلب معاودة الاتصال',
        ];
    }

    public function getChannelLabelAttribute(): string
    {
        return self::channelLabels()[$this->channel] ?? $this->channel;
    }

    public function getChannelIconAttribute(): string
    {
        return self::channelIcons()[$this->channel] ?? 'fas fa-comment';
    }

    public function getResultLabelAttribute(): string
    {
        return self::resultLabels()[$this->result] ?? '-';
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function member()
    {
        return $this->belongsTo(\App\Models\members\Member::class, 'member_id');
    }

    public function followup()
    {
        return $this->belongsTo(crmfollowup::class, 'followup_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
