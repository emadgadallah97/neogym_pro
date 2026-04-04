<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * WhatsApp Message Templates
 * Editable from UI. System templates (is_system=1) cannot be deleted.
 * Usage: WhatsAppTemplate::getRendered('welcome', ['name'=>'أحمد'])
 */
class WhatsAppTemplate extends Model
{
    protected $table = 'whats_app_templates';

    protected $fillable = ['key', 'label', 'body', 'is_active', 'variables', 'is_system'];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * @param  array<string, string>  $data
     * @return string
     */
    public function render(array $data)
    {
        $msg = $this->body;
        foreach ($data as $k => $v) {
            $msg = str_replace('{' . $k . '}', (string) $v, $msg);
        }

        return $msg;
    }

    /**
     * @param  string  $key
     * @return self|null
     */
    public static function getByKey($key)
    {
        return static::where('key', $key)->where('is_active', true)->first();
    }

    /**
     * @param  string  $key
     * @param  array<string, string>  $data
     * @return string|null
     */
    public static function getRendered($key, array $data)
    {
        $t = static::getByKey($key);
        if (! $t) {
            $body = config('whatsapp.templates.' . $key . '.body');
            if (! $body) {
                return null;
            }
            $t = new static(['body' => $body]);
        }

        return $t->render($data);
    }
}
