<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * WhatsApp Message Log
 * Auto-created by WhatsAppService for every send attempt.
 * Usage: WhatsAppLog::todayStats()
 */
class WhatsAppLog extends Model
{
    protected $table = 'whats_app_logs';

    const STATUS_PENDING = 'pending';

    const STATUS_SENT = 'sent';

    const STATUS_FAILED = 'failed';

    /** رسالة واردة من العميل عبر واتساب (تُسجَّل عبر webhook من خدمة Node) */
    const STATUS_RECEIVED = 'received';

    protected $fillable = [
        'phone',
        'template_key',
        'message',
        'status',
        'message_id',
        'error',
        'related_id',
        'related_type',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * @return array<string, int>
     */
    public static function todayStats()
    {
        return [
            'total' => static::today()->count(),
            'sent' => static::today()->sent()->count(),
            'failed' => static::today()->failed()->count(),
            'pending' => static::today()->pending()->count(),
        ];
    }
}
