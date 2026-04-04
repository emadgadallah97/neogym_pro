<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * WhatsApp Settings Model
 * Stores all runtime config in DB (editable from UI).
 * Usage: WhatsAppSetting::get('service_url')
 *        WhatsAppSetting::set('bulk_delay', 2000)
 */
class WhatsAppSetting extends Model
{
    protected $table = 'whats_app_settings';

    protected $fillable = ['key', 'value'];

    /**
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return Cache::remember('wa_setting_' . $key, 3600, function () use ($key, $default) {
            $value = static::where('key', $key)->value('value');

            return $value !== null ? $value : $default;
        });
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public static function set($key, $value)
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value]
        );
        Cache::forget('wa_setting_' . $key);
        Cache::forget('wa_settings_all');
    }

    /**
     * @return array<string, string|null>
     */
    public static function allAsArray()
    {
        return Cache::remember('wa_settings_all', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    /**
     * @return void
     */
    public static function clearAllCache()
    {
        Cache::forget('wa_settings_all');
        foreach (static::pluck('key') as $key) {
            Cache::forget('wa_setting_' . $key);
        }
    }
}
