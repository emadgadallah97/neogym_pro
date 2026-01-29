<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Branch extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'branches';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'address',
        'phone_1',
        'phone_2',
        'whatsapp',
        'email',
        'notes',
        'user_add',
        'status',
    ];

    public $translatable = ['name'];

    protected $dates = ['deleted_at'];
}
