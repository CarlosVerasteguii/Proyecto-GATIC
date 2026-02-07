<?php

namespace App\Support\Settings;

use App\Models\UserSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserSettingsStore
{
    public const THEME_KEY = 'ui.theme';

    public const DENSITY_KEY = 'ui.density';

    public const SIDEBAR_COLLAPSED_KEY = 'ui.sidebar_collapsed';

    private const COLUMNS_PREFIX = 'ui.columns.';

    private const CACHE_PREFIX = 'user-settings:';

    private const CACHE_TTL_SECONDS = 3600;

    /**
     * @return array{
     *   theme?: 'light'|'dark',
     *   density?: 'normal'|'compact',
     *   sidebarCollapsed?: bool,
     *   columns: array<string, list<string>>
     * }
     */
    public function getBootstrapPreferencesForUser(int $userId): array
    {
        $all = $this->getAllForUser($userId);

        $result = [
            'columns' => [],
        ];

        $theme = $this->normalizeTheme($all[self::THEME_KEY] ?? null);
        if ($theme !== null) {
            $result['theme'] = $theme;
        }

        $density = $this->normalizeDensity($all[self::DENSITY_KEY] ?? null);
        if ($density !== null) {
            $result['density'] = $density;
        }

        $sidebarCollapsed = $this->normalizeSidebarCollapsed($all[self::SIDEBAR_COLLAPSED_KEY] ?? null);
        if ($sidebarCollapsed !== null) {
            $result['sidebarCollapsed'] = $sidebarCollapsed;
        }

        foreach ($all as $key => $value) {
            if (! str_starts_with($key, self::COLUMNS_PREFIX)) {
                continue;
            }

            $tableKey = substr($key, strlen(self::COLUMNS_PREFIX));
            if (! $this->isValidTableKey($tableKey)) {
                continue;
            }

            try {
                $result['columns'][$tableKey] = $this->normalizeColumns($value);
            } catch (InvalidArgumentException) {
                // Ignore malformed value and keep processing.
            }
        }

        return $result;
    }

    public function setForUser(int $userId, string $key, mixed $value, ?int $updatedByUserId = null): void
    {
        $normalizedKey = trim($key);
        if ($normalizedKey === '') {
            throw new InvalidArgumentException('Preference key cannot be empty.');
        }

        if ($value === null) {
            $this->forgetForUser($userId, $normalizedKey);

            return;
        }

        $normalizedValue = $this->normalizeValue($normalizedKey, $value);

        if ($this->shouldForget($normalizedKey, $normalizedValue)) {
            $this->forgetForUser($userId, $normalizedKey);

            return;
        }

        UserSetting::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'key' => $normalizedKey,
            ],
            [
                'value' => $normalizedValue,
                'updated_by_user_id' => $updatedByUserId,
            ],
        );

        $this->clearCacheForUser($userId);
    }

    public function forgetForUser(int $userId, string $key): void
    {
        UserSetting::query()
            ->where('user_id', $userId)
            ->where('key', $key)
            ->delete();

        $this->clearCacheForUser($userId);
    }

    public function forgetUiPreferencesForUser(int $userId): int
    {
        $deleted = UserSetting::query()
            ->where('user_id', $userId)
            ->where('key', 'like', 'ui.%')
            ->delete();

        $this->clearCacheForUser($userId);

        return $deleted;
    }

    /**
     * @return array<string, mixed>
     */
    private function getAllForUser(int $userId): array
    {
        $cacheKey = self::CACHE_PREFIX.$userId;

        try {
            if (Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey, []);

                return is_array($cached) ? $cached : [];
            }

            // Avoid `pluck()` here: it bypasses Eloquent casts, and `value` is JSON.
            $all = UserSetting::query()
                ->where('user_id', $userId)
                ->where('key', 'like', 'ui.%')
                ->get(['key', 'value'])
                ->mapWithKeys(fn (UserSetting $setting): array => [$setting->key => $setting->value])
                ->all();

            Cache::put($cacheKey, $all, self::CACHE_TTL_SECONDS);

            return $all;
        } catch (\Throwable $e) {
            Log::warning('UserSettingsStore: failed to load preferences', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    private function clearCacheForUser(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX.$userId);
    }

    private function normalizeValue(string $key, mixed $value): mixed
    {
        if ($key === self::THEME_KEY) {
            $theme = $this->normalizeTheme($value);
            if ($theme === null) {
                throw new InvalidArgumentException('Invalid theme value.');
            }

            return $theme;
        }

        if ($key === self::DENSITY_KEY) {
            $density = $this->normalizeDensity($value);
            if ($density === null) {
                throw new InvalidArgumentException('Invalid density value.');
            }

            return $density;
        }

        if ($key === self::SIDEBAR_COLLAPSED_KEY) {
            $sidebar = $this->normalizeSidebarCollapsed($value);
            if ($sidebar === null) {
                throw new InvalidArgumentException('Invalid sidebar value.');
            }

            return $sidebar;
        }

        if (str_starts_with($key, self::COLUMNS_PREFIX)) {
            $tableKey = substr($key, strlen(self::COLUMNS_PREFIX));
            if (! $this->isValidTableKey($tableKey)) {
                throw new InvalidArgumentException('Invalid columns key.');
            }

            return $this->normalizeColumns($value);
        }

        throw new InvalidArgumentException('Unsupported preference key.');
    }

    private function shouldForget(string $key, mixed $value): bool
    {
        if ($key === self::DENSITY_KEY) {
            return $value === 'normal';
        }

        if ($key === self::SIDEBAR_COLLAPSED_KEY) {
            return $value === false;
        }

        if (str_starts_with($key, self::COLUMNS_PREFIX)) {
            return is_array($value) && $value === [];
        }

        return false;
    }

    /**
     * @return 'light'|'dark'|null
     */
    private function normalizeTheme(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return in_array($value, ['light', 'dark'], true) ? $value : null;
    }

    /**
     * @return 'normal'|'compact'|null
     */
    private function normalizeDensity(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return in_array($value, ['normal', 'compact'], true) ? $value : null;
    }

    private function normalizeSidebarCollapsed(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            if ($value === 1) {
                return true;
            }

            if ($value === 0) {
                return false;
            }

            return null;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false'], true)) {
                return false;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function normalizeColumns(mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException('Invalid columns payload.');
        }

        $normalized = [];

        foreach ($value as $columnKey) {
            if (! is_string($columnKey)) {
                throw new InvalidArgumentException('Columns must be strings.');
            }

            $trimmed = trim($columnKey);
            if ($trimmed === '' || strlen($trimmed) > 80) {
                continue;
            }

            if (! preg_match('/^[a-z0-9][a-z0-9._-]*$/i', $trimmed)) {
                continue;
            }

            $normalized[$trimmed] = true;
        }

        return array_keys($normalized);
    }

    private function isValidTableKey(string $tableKey): bool
    {
        if ($tableKey === '' || strlen($tableKey) > 80) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9][a-z0-9._-]*$/i', $tableKey);
    }
}
