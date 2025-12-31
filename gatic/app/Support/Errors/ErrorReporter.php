<?php

namespace App\Support\Errors;

use App\Models\ErrorReport;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ErrorReporter
{
    public function report(Throwable $throwable, Request $request): string
    {
        $context = $this->buildContext($request);

        $errorId = $this->persistBestEffort($throwable, $context);

        Log::error('Unhandled exception', [
            'error_id' => $errorId,
            'environment' => app()->environment(),
            'user_id' => $context['user']['id'] ?? null,
            'route' => $context['request']['route'] ?? null,
            'method' => $context['request']['method'] ?? null,
            'path' => $context['request']['path'] ?? null,
            'exception_class' => $throwable::class,
            'exception_message' => $this->sanitizeText($throwable->getMessage()),
        ]);

        return $errorId;
    }

    private function persistBestEffort(Throwable $throwable, array $context): string
    {
        $errorId = $this->generateErrorId();

        if (! (bool) config('gatic.errors.reporting.enabled', true)) {
            return $errorId;
        }

        $maxAttempts = 3;

        try {
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    ErrorReport::query()->create([
                        'error_id' => $errorId,
                        'environment' => app()->environment(),
                        'user_id' => $context['user']['id'] ?? null,
                        'user_role' => $context['user']['role'] ?? null,
                        'method' => $context['request']['method'] ?? null,
                        'url' => $context['request']['url'] ?? null,
                        'route' => $context['request']['route'] ?? null,
                        'exception_class' => $throwable::class,
                        'exception_message' => $this->sanitizeText($throwable->getMessage()),
                        'stack_trace' => $this->sanitizeText($throwable->getTraceAsString()),
                        'context' => $context,
                    ]);

                    return $errorId;
                } catch (QueryException $e) {
                    if ($attempt === $maxAttempts) {
                        throw $e;
                    }

                    $errorId = $this->generateErrorId();
                }
            }
        } catch (Throwable $e) {
            Log::warning('Error report persistence failed', [
                'error_id' => $errorId,
                'exception' => $e,
            ]);
        }

        return $errorId;
    }

    private function generateErrorId(): string
    {
        return (string) Str::ulid();
    }

    private function buildContext(Request $request): array
    {
        $route = $request->route();
        $user = $request->user();

        return [
            'request' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'url' => $request->url(),
                'route' => $route?->getName(),
                'query_keys' => array_keys($request->query()),
                'input_keys' => array_keys($request->except([
                    'password',
                    'password_confirmation',
                    'current_password',
                    'token',
                ])),
                'headers' => $this->allowlistedHeaders($request),
            ],
            'user' => [
                'id' => $user?->getAuthIdentifier(),
                'role' => $user?->role?->value,
            ],
        ];
    }

    private function allowlistedHeaders(Request $request): array
    {
        $headers = [];

        foreach (['accept', 'user-agent', 'referer'] as $key) {
            $value = $request->headers->get($key);
            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($key === 'referer') {
                $headers[$key] = $this->sanitizeReferer($value);
                continue;
            }

            $headers[$key] = $this->sanitizeText($value);
        }

        return $headers;
    }

    private function sanitizeReferer(string $referer): string
    {
        $trimmed = trim($referer);
        if ($trimmed === '') {
            return '';
        }

        $trimmed = explode('#', $trimmed, 2)[0];
        $trimmed = explode('?', $trimmed, 2)[0];

        $parts = parse_url($trimmed);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return $this->sanitizeText($trimmed);
        }

        $path = isset($parts['path']) && is_string($parts['path']) ? $parts['path'] : '';

        return $parts['scheme'].'://'.$parts['host'].$path;
    }

    private function sanitizeText(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        if ($value === '') {
            return $value;
        }

        $redactions = [
            '/(?i)\\b(password|pwd|passphrase|secret|token|api[_-]?key)\\b\\s*[:=]\\s*[^\\s,;]++/' => '$1=[REDACTED]',
            '/(?i)\\b(authorization)\\b\\s*[:=]\\s*[^\\s,;]++/' => '$1=[REDACTED]',
            '/(?i)\\bbearer\\s+[A-Za-z0-9\\-\\._~\\+\\/]+=*/' => 'Bearer [REDACTED]',
        ];

        $redacted = preg_replace(array_keys($redactions), array_values($redactions), $value);

        return is_string($redacted) ? $redacted : $value;
    }
}
