<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;

class application_settings extends Model 
{
    use HasTranslations;
    protected $fillable = [
        'name',
        'id_settings_type',
        'user_add',

    ];
    public $translatable = ['name'];
    protected $table = 'application_settings';
    public $timestamps = true;

    use SoftDeletes;

    protected $dates = ['deleted_at'];
    public function settings_type(){
        return $this->belongsTo (settings_type::class, 'id_settings_type');
    }
}