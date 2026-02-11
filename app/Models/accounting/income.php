<?php

namespace App\Models\accounting;

use App\Models\general\Branch;
use App\Models\employee\employee as Employee;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class income extends Model
{
    use SoftDeletes;

    protected $table = 'incomes';

    protected $fillable = [
        'branchid',
        'income_type_id',
        'incomedate',
        'amount',
        'paymentmethod',
        'receivedbyemployeeid',
        'payername',
        'payerphone',
        'description',
        'notes',
        'iscancelled',
        'usercancel',
        'cancelledat',
        'cancelreason',
        'useradd',
        'userupdate',
    ];

    protected $casts = [
        'incomedate' => 'date',
        'amount' => 'decimal:2',
        'iscancelled' => 'boolean',
        'cancelledat' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchid');
    }

    public function type()
    {
        return $this->belongsTo(IncomeType::class, 'income_type_id');
    }

    public function receivedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'receivedbyemployeeid');
    }
}
