<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;

class nationalities extends Model
{

    use HasTranslations;
    protected $fillable = [
        'name',
        'default',
        'id_country',
        'status',

    ];
    public $translatable = ['name'];
    protected $table = 'nationalities_settings';
    public $timestamps = true;
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    public function countries(){
        return $this->belongsTo (countries::class, 'id_country');
    }

}
