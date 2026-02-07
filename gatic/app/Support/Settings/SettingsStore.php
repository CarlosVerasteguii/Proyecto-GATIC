<?php

namespace App\Support\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized settings reader with DB override + config fallback.
 *
 * Usage:
 *   $store = app(SettingsStore::class);
 *   $store->getInt('gatic.alerts.loans.due_soon_window_days_default');
 *   $store->set('gatic.alerts.loans.due_soon_window_days_default', 14, $userId);
 */
class SettingsStore
{
    /**
     * Whitelist of keys allowed in this story scope.
     *
     * @var list<string>
     */
    public const ALLOWED_KEYS = [
        'gatic.alerts.loans.due_soon_window_days_default',
        'gatic.alerts.loans.due_soon_window_days_options',
        'gatic.alerts.warranties.due_soon_window_days_default',
        'gatic.alerts.warranties.due_soon_window_days_options',
        'gatic.alerts.renewals.due_soon_window_days_default',
        'gatic.alerts.renewals.due_soon_window_days_options',
        'gatic.inventory.money.default_currency',
    ];

    private const CACHE_PREFIX = 'settings:';

    private const CACHE_TTL_SECONDS = 3600;

    /**
     * Get a setting value (DB override first, then config fallback).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (! in_array($key, self::ALLOWED_KEYS, true)) {
            return config($key, $default);
        }

        $cacheKey = self::CACHE_PREFIX.$key;

        try {
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $setting = Setting::query()->where('key', $key)->first();
            $value = $setting !== null ? $setting->value : config($key, $default);

            if ($value !== null) {
                Cache::put($cacheKey, $value, self::CACHE_TTL_SECONDS);
            }

            return $value;
        } catch (\Throwable $e) {
            Log::warning('SettingsStore: DB read failed, falling back to config', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }

        return config($key, $default);
    }

    /**
     * Get a setting as integer.
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Get a setting as string.
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_string($value) ? $value : $default;
    }

    /**
     * Get a setting as a list of integers.
     *
     * @return list<int>
     */
    public function getIntList(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        if (! is_array($value) || $value === []) {
            return array_values(array_map('intval', $default));
        }

        return array_values(array_unique(array_map('intval', $value)));
    }

    /**
     * Set a setting value (creates or updates).
     */
    public function set(string $key, mixed $value, ?int $userId = null): void
    {
        if (! in_array($key, self::ALLOWED_KEYS, true)) {
            return;
        }

        if ($value === null) {
            $this->forget($key);

            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'updated_by_user_id' => $userId,
            ],
        );

        Cache::put(self::CACHE_PREFIX.$key, $value, self::CACHE_TTL_SECONDS);
    }

    /**
     * Remove a setting override (restore to config default).
     */
    public function forget(string $key): void
    {
        if (! in_array($key, self::ALLOWED_KEYS, true)) {
            return;
        }

        Setting::query()->where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX.$key);
    }

    /**
     * Remove all setting overrides (restore all to config defaults).
     */
    public function forgetAll(): void
    {
        Setting::query()->whereIn('key', self::ALLOWED_KEYS)->delete();

        foreach (self::ALLOWED_KEYS as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }

    /**
     * Check if a key has a DB override.
     */
    public function hasOverride(string $key): bool
    {
        if (! in_array($key, self::ALLOWED_KEYS, true)) {
            return false;
        }

        return Setting::query()->where('key', $key)->exists();
    }

    /**
     * Get all current overrides as [key => value].
     *
     * @return array<string, mixed>
     */
    public function getAllOverrides(): array
    {
        $settings = Setting::query()
            ->whereIn('key', self::ALLOWED_KEYS)
            ->pluck('value', 'key')
            ->toArray();

        return $settings;
    }

    /**
     * Clear all cached settings.
     */
    public function clearCache(): void
    {
        foreach (self::ALLOWED_KEYS as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }
}
