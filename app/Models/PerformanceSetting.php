<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PerformanceSetting extends Model
{
    protected $connection = 'tenant';

    protected $table = 'performance_settings';

    protected $fillable = ['key', 'value'];

    public static function defaultKeys(): array
    {
        return [
            'cache_ttl' => (string) 3600,
            'minify_html' => '0',
            'lazy_load_images' => '1',
            'cdn_base_url' => '',
        ];
    }

    /** Get a setting value with optional default. */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $defaults = self::defaultKeys();
        $fallback = $default ?? ($defaults[$key] ?? null);

        $record = self::where('key', $key)->first();

        return $record !== null ? $record->value : $fallback;
    }

    /** Set a setting value. */
    public static function setValue(string $key, ?string $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value ?? '']
        );
        self::clearCache();
    }

    /** Get all current settings (from DB + defaults). */
    public static function getAll(): array
    {
        $cacheKey = 'performance_settings_all';
        return Cache::remember($cacheKey, 60, function () {
            $defaults = self::defaultKeys();
            $rows = self::pluck('value', 'key')->toArray();
            return array_merge($defaults, $rows);
        });
    }

    /** Set multiple settings at once. */
    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (array_key_exists($key, self::defaultKeys())) {
                self::setValue($key, $value === null || $value === '' ? null : (string) $value);
            }
        }
    }

    public static function clearCache(): void
    {
        Cache::forget('performance_settings_all');
    }
}
