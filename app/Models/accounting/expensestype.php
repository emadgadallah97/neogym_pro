<?php

namespace App\Models\accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class expensestype extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'expenses_types';

    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'status',
        'useradd',
        'userupdate',
    ];

    protected $casts = [
        'name'   => 'array',
        'status' => 'boolean',
    ];
}
