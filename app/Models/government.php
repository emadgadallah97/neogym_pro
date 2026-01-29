<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;

class government extends Model
{

    use HasTranslations;
    protected $fillable = [
        'name',
        'notes',
        'user_add',

    ];
    public $translatable = ['name'];
    protected $table = 'government';
    public $timestamps = true;
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function countries(){
        return $this->belongsTo (countries::class, 'id_country');
    }


}
