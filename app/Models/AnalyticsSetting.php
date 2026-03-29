<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AnalyticsSetting extends Model
{
    protected $table = 'analytics_settings';

    protected $fillable = ['key', 'value'];

    public static function defaultKeys(): array
    {
        return [
            'gsc_site_url' => '',
            'ga_measurement_id' => '',
        ];
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $defaults = self::defaultKeys();
        $fallback = $default ?? ($defaults[$key] ?? null);
        $record = self::where('key', $key)->first();
        return $record !== null ? $record->value : $fallback;
    }

    public static function setValue(string $key, ?string $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value ?? '']
        );
        self::clearCache();
    }

    public static function getAll(): array
    {
        $cacheKey = 'analytics_settings_all';
        return Cache::remember($cacheKey, 60, function () {
            $defaults = self::defaultKeys();
            $rows = self::pluck('value', 'key')->toArray();
            return array_merge($defaults, $rows);
        });
    }

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
        Cache::forget('analytics_settings_all');
    }
}
