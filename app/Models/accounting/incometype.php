<?php

namespace App\Models\accounting;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class incometype extends Model
{
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'income_types';

    protected $fillable = [
        'name',
        'status',
        'notes',
        'useradd',
        'userupdate',
    ];

    public $translatable = ['name'];

    protected $casts = [
        'status' => 'boolean',
        'name' => 'array',
    ];

    public function incomes()
    {
        return $this->hasMany(Income::class, 'income_type_id');
    }
}
