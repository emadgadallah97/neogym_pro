<?php

namespace App\Models\subscriptions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class subscriptions_plan_branch_price extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'subscriptions_plan_branch_prices';

    protected $fillable = [
        'subscriptions_plan_id',
        'branch_id',
        'price_without_trainer',
        'trainer_pricing_mode',
        'trainer_uniform_price',
        'trainer_default_price',
    ];
}
