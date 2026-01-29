<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class GeneralSetting extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'general_settings';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'logo',
        'country_id',
        'currency_id',
        'commercial_register',
        'tax_register',
        'phone',
        'email',
        'website',
        'notes',
        'user_add',
        'status',
    ];

    public $translatable = ['name'];

    protected $dates = ['deleted_at'];

    public function country()
    {
        // موديل الدول عندك: App\models\countries
        return $this->belongsTo(\App\models\countries::class, 'country_id');
    }

    public function currency()
    {
        // هل لديك موديل للعملات؟ لو اسمه مختلف ابعته لي وعدّله لك فورًا
        return $this->belongsTo(\App\models\currencies::class, 'currency_id');
    }
}
