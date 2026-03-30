<?php

namespace App\Models\accounting;

use App\User;
use App\Models\general\Branch;
use Illuminate\Database\Eloquent\Model;

class TreasuryTransaction extends Model
{
    protected $table = 'treasury_transactions';

    // Treasury transactions are IMMUTABLE — never soft-deleted, only reversed
    protected $fillable = [
        'period_id',
        'branch_id',
        'user_id',
        'type',
        'amount',
        'source_type',
        'source_id',
        'is_reversal',
        'reversal_of',
        'category',
        'description',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
        'is_reversal'      => 'boolean',
        'period_id'        => 'integer',
        'branch_id'        => 'integer',
        'user_id'          => 'integer',
        'source_id'        => 'integer',
        'reversal_of'      => 'integer',
    ];

    // ─────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────

    public function period()
    {
        return $this->belongsTo(TreasuryPeriod::class, 'period_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function originalTransaction()
    {
        return $this->belongsTo(TreasuryTransaction::class, 'reversal_of');
    }

    public function reversals()
    {
        return $this->hasMany(TreasuryTransaction::class, 'reversal_of');
    }
}
