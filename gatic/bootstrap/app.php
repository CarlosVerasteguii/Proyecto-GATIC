<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        $middleware->append(\App\Http\Middleware\PerfRequestTelemetry::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, Request $request) {
            $isLivewire = $request->headers->has('X-Livewire');
            $expectsJson = $request->expectsJson() || $isLivewire;

            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return null;
            }

            if ($e instanceof HttpResponseException && $e->getResponse()->getStatusCode() < 500) {
                return null;
            }

            if (
                $e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof ValidationException
                || $e instanceof TokenMismatchException
            ) {
                return null;
            }

            if (
                ! $expectsJson
                && (bool) config('app.debug', false)
                && app()->environment(['local', 'testing'])
            ) {
                $allowDebugPage = false;

                try {
                    $user = $request->user();
                    $allowDebugPage = $user !== null && ($user->role?->value ?? null) === 'Admin';
                } catch (\Throwable) {
                    $allowDebugPage = false;
                }

                if ($allowDebugPage) {
                    return null;
                }
            }

            $errorId = app(\App\Support\Errors\ErrorReporter::class)->report($e, $request);

            if ($expectsJson) {
                return response()->json([
                    'message' => 'Ocurrió un error inesperado.',
                    'error_id' => $errorId,
                ], 500);
            }

            return response()->view('errors.500', ['errorId' => $errorId], 500);
        });
    })->create();
