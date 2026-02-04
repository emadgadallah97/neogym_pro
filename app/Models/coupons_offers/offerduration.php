<?php

namespace App\Models\coupons_offers;

use Illuminate\Database\Eloquent\Model;

class OfferDuration extends Model
{
    protected $table = 'offer_durations';

    protected $fillable = [
        'offer_id',
        'duration_value',
        'duration_unit',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }
}
