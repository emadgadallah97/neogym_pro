<?php

namespace App\Models\accounting;

use App\Models\accounting\CommissionSettlement;
use App\Models\employee\employee as Employee;
use App\Models\general\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $table = 'expenses';

    protected $fillable = [
        'branchid',
        'expensestypeid',
        'expensedate',
        'amount',
        'recipientname',
        'recipientphone',
        'recipientnationalid',
        'disbursedbyemployeeid',
        'description',
        'notes',
        'iscancelled',
        'cancelledat',
        'cancelledby',
        'useradd',
        'userupdate',
        'commission_settlement_id',
        'hr_advance_id',
    ];

    protected $casts = [
        'expensedate' => 'date',
        'amount'      => 'decimal:2',
        'iscancelled' => 'boolean',
        'cancelledat' => 'datetime',

        'branchid' => 'integer',
        'expensestypeid' => 'integer',
        'disbursedbyemployeeid' => 'integer',
        'cancelledby' => 'integer',
        'useradd' => 'integer',
        'userupdate' => 'integer',
    ];

    public function type()
    {
        // لو النوع بقى inactive أو soft-deleted نفضل نعرضه في السجل
        return $this->belongsTo(ExpensesType::class, 'expensestypeid')->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchid');
    }

    public function disburserEmployee()
    {
        return $this->belongsTo(Employee::class, 'disbursedbyemployeeid');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'useradd');
    }

    public function updater()
    {
        return $this->belongsTo(\App\User::class, 'userupdate');
    }

    public function canceller()
    {
        return $this->belongsTo(\App\User::class, 'cancelledby');
    }

    public function settlement()
    {
        return $this->belongsTo(CommissionSettlement::class, 'commission_settlement_id');
    }

    public function advance()
    {
        return $this->belongsTo(\App\Models\hr\HrAdvance::class, 'hr_advance_id');
    }
}
