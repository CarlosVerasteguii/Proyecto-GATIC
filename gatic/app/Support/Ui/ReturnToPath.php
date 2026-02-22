<?php

declare(strict_types=1);

namespace App\Support\Ui;

final class ReturnToPath
{
    public static function sanitize(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || ! str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return null;
        }

        if (str_contains($value, "\n") || str_contains($value, "\r") || strlen($value) > 2000) {
            return null;
        }

        return $value;
    }

    /**
     * Returns the current browser URL path (and query) as an internal path.
     *
     * In Livewire requests, Laravel's request() points to the update endpoint, so we
     * fall back to the Referer header (which should be the browser page URL).
     *
     * @param  list<string>  $exceptQueryKeys
     */
    public static function browserCurrent(array $exceptQueryKeys = []): ?string
    {
        if (request()->headers->has('X-Livewire')) {
            return self::fromReferer($exceptQueryKeys);
        }

        return self::current($exceptQueryKeys);
    }

    /**
     * @param  list<string>  $exceptQueryKeys
     */
    public static function fromReferer(array $exceptQueryKeys = []): ?string
    {
        $referer = request()->headers->get('referer');
        if (! is_string($referer)) {
            return null;
        }

        $referer = trim($referer);
        if ($referer === '' || str_contains($referer, "\n") || str_contains($referer, "\r")) {
            return null;
        }

        $parts = parse_url($referer);
        if (! is_array($parts)) {
            return null;
        }

        $host = $parts['host'] ?? null;
        if (is_string($host) && $host !== '' && strcasecmp($host, request()->getHost()) !== 0) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        if (! is_string($path) || $path === '') {
            $path = '/';
        }

        $query = [];
        if (is_string($parts['query'] ?? null) && $parts['query'] !== '') {
            parse_str($parts['query'], $query);
        }

        foreach ($exceptQueryKeys as $key) {
            if ($key !== '') {
                unset($query[$key]);
            }
        }

        return self::sanitize(self::withQuery($path, $query));
    }

    public static function queryParamFromReferer(string $key): ?string
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        $referer = request()->headers->get('referer');
        if (! is_string($referer)) {
            return null;
        }

        $referer = trim($referer);
        if ($referer === '' || str_contains($referer, "\n") || str_contains($referer, "\r")) {
            return null;
        }

        $parts = parse_url($referer);
        if (! is_array($parts)) {
            return null;
        }

        $host = $parts['host'] ?? null;
        if (is_string($host) && $host !== '' && strcasecmp($host, request()->getHost()) !== 0) {
            return null;
        }

        $queryString = $parts['query'] ?? null;
        if (! is_string($queryString) || $queryString === '') {
            return null;
        }

        $query = [];
        parse_str($queryString, $query);

        $value = $query[$key] ?? null;
        if (! is_scalar($value)) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param  list<string>  $exceptQueryKeys
     */
    public static function current(array $exceptQueryKeys = []): ?string
    {
        $path = request()->getPathInfo();
        if ($path === '') {
            $path = '/';
        }

        $query = request()->query();
        foreach ($exceptQueryKeys as $key) {
            if ($key !== '') {
                unset($query[$key]);
            }
        }

        return self::sanitize(self::withQuery($path, $query));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function withQuery(string $path, array $query): string
    {
        $sanitized = self::sanitize($path) ?? '/';
        $parts = parse_url($sanitized);

        $resolvedPath = '/';
        $existingQuery = [];

        if (is_array($parts)) {
            if (is_string($parts['path'] ?? null) && $parts['path'] !== '') {
                $resolvedPath = $parts['path'];
            }

            if (is_string($parts['query'] ?? null) && $parts['query'] !== '') {
                parse_str($parts['query'], $existingQuery);
            }
        }

        foreach ($query as $key => $value) {
            if ($key === '') {
                continue;
            }

            if ($value === null || $value === '') {
                unset($existingQuery[$key]);

                continue;
            }

            $existingQuery[$key] = $value;
        }

        $queryString = http_build_query($existingQuery);

        return $queryString === '' ? $resolvedPath : "{$resolvedPath}?{$queryString}";
    }
}
