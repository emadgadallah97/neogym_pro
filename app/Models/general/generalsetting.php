<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
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
        'member_card_template',
        'user_add',
        'status',
    ];

    public $translatable = ['name'];

    protected $dates = ['deleted_at'];

    public function country()
    {
        return $this->belongsTo(\App\models\countries::class, 'country_id');
    }

    public function currency()
    {
        return $this->belongsTo(\App\models\currencies::class, 'currency_id');
    }

    public static function defaultMemberCardTemplate(): string
    {
        return 'default';
    }

    /**
     * يقرأ القوالب تلقائياً من:
     * resources/views/members/cards/*.blade.php
     * مع تجاهل الملفات التي تبدأ بـ "_" (partials/layout)
     */
    public static function availableMemberCardTemplates(): array
    {
        $dir = resource_path('views/members/cards');

        $templates = [];

        if (is_dir($dir)) {
            foreach (File::files($dir) as $file) {
                $name = $file->getFilenameWithoutExtension();

                if (str_starts_with($name, '_')) {
                    continue;
                }

                $label = ucwords(str_replace(['-', '_'], ' ', $name));
                $templates[$name] = $label;
            }
        }

        if (empty($templates)) {
            $templates = [
                self::defaultMemberCardTemplate() => 'Default',
            ];
        }

        ksort($templates);

        return $templates;
    }
}
