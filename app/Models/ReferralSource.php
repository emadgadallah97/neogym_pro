<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class ReferralSource extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'referral_sources';

    protected $fillable = [
        'name',
        'status',
        'sort_order',
        'notes',
        'useradd',
        'userupdate',
    ];

    public $translatable = ['name'];

    protected $casts = [
        'status' => 'boolean',
        'name' => 'array',
        'sort_order' => 'integer',
    ];
}
