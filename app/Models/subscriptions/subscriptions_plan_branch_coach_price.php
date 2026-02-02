<?php

namespace App\Models\subscriptions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class subscriptions_plan_branch_coach_price extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'subscriptions_plan_branch_coach_prices';

    protected $fillable = [
        'subscriptions_plan_id',
        'branch_id',
        'employee_id',
        'is_included',
        'price',
    ];

    protected $casts = [
        'is_included' => 'boolean',
    ];
}
