<?php

namespace App\models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class log extends Model 
{
    protected $fillable =
     [
        'id_screen',
        'event_screen',
        'event',
        'event_type',
        'user',
        'method',
        'fullurl',
     ];
    
    protected $table = 'log';
    public $timestamps = true;
    use SoftDeletes;
    protected $dates = ['deleted_at'];

}