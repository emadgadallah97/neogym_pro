<?php

namespace App\Models\employee;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Job extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'jobs';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'code',
        'description',
        'notes',
        'user_add',
        'status',
    ];

    public $translatable = ['name'];

    protected $dates = ['deleted_at'];
}
