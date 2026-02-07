<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Model;

class TrainerSessionPricing extends Model
{
    protected $table = 'trainer_session_pricings';

    protected $fillable = [
        'trainer_id',
        'session_price',
        'updated_by',
    ];

    protected $casts = [
        'session_price' => 'decimal:2',
        'trainer_id' => 'integer',
        'updated_by' => 'integer',
    ];
}
