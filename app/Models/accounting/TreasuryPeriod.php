<?php

namespace App\Models\accounting;

use App\Models\general\Branch;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TreasuryPeriod extends Model
{
    protected $table = 'treasury_periods';

    protected $fillable = [
        'branch_id',
        'name',
        'start_date',
        'end_date',
        'opening_balance',
        'closing_balance',
        'handed_over',
        'carried_forward',
        'status',
        'opened_by',
        'closed_by',
        'closed_at',
        'close_notes',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'closed_at'       => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'handed_over'     => 'decimal:2',
        'carried_forward' => 'decimal:2',
        'branch_id'       => 'integer',
        'opened_by'       => 'integer',
        'closed_by'       => 'integer',
    ];

    // ─────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions()
    {
        return $this->hasMany(TreasuryTransaction::class, 'period_id');
    }

    // ─────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Calculate closing balance from opening + in - out
     */
    public function calcClosingBalance(): float
    {
        $totalIn  = $this->transactions()->where('type', 'in')->sum('amount');
        $totalOut = $this->transactions()->where('type', 'out')->sum('amount');
        return (float) $this->opening_balance + (float) $totalIn - (float) $totalOut;
    }
}
