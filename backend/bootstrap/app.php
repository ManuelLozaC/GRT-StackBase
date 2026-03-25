<?php

use App\Core\Errors\ErrorLogger;
use App\Core\Http\Exceptions\ApiProblem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', \App\Http\Middleware\AttachRequestContext::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\SanitizeApiInput::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\SetApiSecurityHeaders::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\TrackApiPerformance::class);

        $middleware->alias([
            'auth-token' => \App\Http\Middleware\ApiTokenAuth::class,
            'tenant-context' => \App\Http\Middleware\SetTenantContext::class,
            'permission' => \App\Http\Middleware\RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ApiProblem $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $meta = array_merge($exception->meta(), [
                'error_code' => $exception->errorCode(),
            ]);

            if ($request->attributes->has('request_id')) {
                $meta['request_id'] = $request->attributes->get('request_id');
            }

            return response()->json([
                'estado' => 'error',
                'datos' => null,
                'mensaje' => $exception->getMessage(),
                'meta' => $meta,
                'errores' => $exception->errors(),
            ], $exception->status());
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return null;
            }

            if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
                return null;
            }

            $log = app(ErrorLogger::class)->log(
                exception: $exception,
                actor: $request->user(),
                context: [
                    'method' => $request->method(),
                    'path' => $request->path(),
                ],
            );

            $meta = [
                'error_code' => 'internal_error',
                'error_log_id' => $log->id,
            ];

            if ($request->attributes->has('request_id')) {
                $meta['request_id'] = $request->attributes->get('request_id');
            }

            return response()->json([
                'estado' => 'error',
                'datos' => null,
                'mensaje' => 'Se produjo un error interno controlado.',
                'meta' => $meta,
                'errores' => [],
            ], 500);
        });
    })->create();
