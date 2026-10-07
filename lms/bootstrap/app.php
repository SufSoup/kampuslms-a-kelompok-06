<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Pendaftaran alias middleware (Laravel 12 — bukan Kernel.php)
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\HttpException $e,
            $request
        ) {
            if ($request->is('api/*') && $e->getStatusCode() === 403) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
                ], 403);
            }
        });

        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Data yang diberikan tidak valid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })
    ->create();