<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;
class countries extends Model
{

    use HasTranslations;
    protected $fillable = [
        'name',
        'notes',
        'user_add',

    ];
    public $translatable = ['name'];
    protected $table = 'countries';
    public $timestamps = true;
    use SoftDeletes;
    protected $dates = ['deleted_at'];



}
