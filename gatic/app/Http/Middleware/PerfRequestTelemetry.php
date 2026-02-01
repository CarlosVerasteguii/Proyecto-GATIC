<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PerfRequestTelemetry
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        if (! (bool) config('gatic.perf.log_enabled', false)) {
            return $next($request);
        }

        $requestId = (string) Str::uuid();
        $start = hrtime(true);

        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $response = $next($request);

        $queries = $connection->getQueryLog();
        $connection->disableQueryLog();

        $durationMs = (hrtime(true) - $start) / 1_000_000;

        $queryCount = count($queries);
        $queryTotalMs = array_sum(array_map(
            fn (array $q): float => (float) ($q['time'] ?? 0),
            $queries
        ));

        $slowestQuery = null;
        foreach ($queries as $q) {
            if (! is_array($q)) {
                continue;
            }

            if ($slowestQuery === null || (float) ($q['time'] ?? 0) > (float) ($slowestQuery['time'] ?? 0)) {
                $slowestQuery = $q;
            }
        }

        $responseBytes = null;
        try {
            $content = $response->getContent();
            if (is_string($content)) {
                $responseBytes = strlen($content);
            }
        } catch (\Throwable) {
            $responseBytes = null;
        }

        $response->headers->set('X-Perf-Id', $requestId);

        Log::channel('perf')->info('perf', [
            'id' => $requestId,
            'method' => $request->getMethod(),
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($durationMs, 2),
            'query_count' => $queryCount,
            'query_total_ms' => round($queryTotalMs, 2),
            'slowest_query_ms' => $slowestQuery ? round((float) ($slowestQuery['time'] ?? 0), 2) : null,
            'slowest_query_sql' => $slowestQuery['query'] ?? null,
            'response_bytes' => $responseBytes,
            'is_livewire' => $request->headers->has('X-Livewire'),
        ]);

        return $response;
    }
}
