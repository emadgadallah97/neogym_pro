<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;
class City extends Model
{
    use HasTranslations;
    protected $fillable = [
        'name',
        'id_country',
        'id_government',
        'user_add',

    ];
    public $translatable = ['name'];
    protected $table = 'city';
    public $timestamps = true;
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    public function governmentes()
    {
        return $this->belongsTo (government::class, 'id_government');
    }
    public function countries()
    {
        return $this->belongsTo (countries::class, 'id_country');
    }


}
