<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Model;

class commissionsetting extends Model
{
    protected $table = 'commission_settings';

    protected $fillable = [
        'calculate_commission_before_discounts',
    ];

    protected $casts = [
        'calculate_commission_before_discounts' => 'boolean',
    ];
}
