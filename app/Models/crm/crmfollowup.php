<?php

namespace App\Models\crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmFollowup extends Model
{
    use SoftDeletes;

    protected $table = 'crm_followups';

    protected $fillable = [
        'member_id',
        'branch_id',
        'type',
        'status',
        'priority',
        'notes',
        'next_action_at',
        'result',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'next_action_at' => 'datetime',
    ];

    // ══════════════════════════════════════════════════════
    //  Labels & Types
    // ══════════════════════════════════════════════════════

    public static function typeLabels(): array
    {
        return [
            'renewal'  => 'تجديد اشتراك',
            'freeze'   => 'إلغاء تجميد',
            'inactive' => 'عضو غير نشط',
            'debt'     => 'تحصيل مديونية',
            'general'  => 'متابعة عامة',

            // ✅ جديد لاستخدامه مع الأعضاء المحتملين
            'prospect' => 'عميل محتمل',
        ];
    }

    /**
     * alias مستخدم في الكنترولر — يعيد نفس typeLabels
     */
    public static function getTypes(): array
    {
        return self::typeLabels();
    }

    public static function priorityLabels(): array
    {
        return [
            'high'   => 'عالية',
            'medium' => 'متوسطة',
            'low'    => 'منخفضة',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'pending'   => 'قيد المتابعة',
            'done'      => 'منتهية',
            'cancelled' => 'ملغاة',
        ];
    }

    // ══════════════════════════════════════════════════════
    //  Accessors
    // ══════════════════════════════════════════════════════

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::priorityLabels()[$this->priority] ?? $this->priority;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending'
            && $this->next_action_at
            && $this->next_action_at->lt(now());
    }

    // ✅ كان مفقوداً — مستخدم في views
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'done'      => 'success',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'high'   => 'danger',
            'medium' => 'warning',
            'low'    => 'secondary',
            default  => 'secondary',
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'renewal'  => 'primary',
            'freeze'   => 'info',
            'inactive' => 'warning',
            'debt'     => 'danger',
            'general'  => 'secondary',

            // ✅ جديد
            'prospect' => 'success',

            default    => 'secondary',
        };
    }

    // ══════════════════════════════════════════════════════
    //  Scopes
    // ══════════════════════════════════════════════════════

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    public function scopeOverdue($q)
    {
        return $q->where('status', 'pending')
            ->whereNotNull('next_action_at')
            ->whereDate('next_action_at', '<', today());
    }

    public function scopeDueToday($q)
    {
        return $q->where('status', 'pending')
            ->whereDate('next_action_at', today());
    }

    // ══════════════════════════════════════════════════════
    //  Relationships
    // ══════════════════════════════════════════════════════

    public function member()
    {
        return $this->belongsTo(\App\Models\members\Member::class, 'member_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function interactions()
    {
        return $this->hasMany(CrmInteraction::class, 'followup_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
