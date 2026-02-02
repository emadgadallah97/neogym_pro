<?php

namespace App\Models\subscriptions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class subscriptions_plan_branch extends Model
{
    use HasFactory;

    protected $table = 'subscriptions_plan_branches';

    protected $fillable = [
        'subscriptions_plan_id',
        'branch_id',
    ];
}
