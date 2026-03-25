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
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
<<<<<<< HEAD
            'auth-token' => \App\Http\Middleware\ApiTokenAuth::class,
            'permission' => \App\Http\Middleware\RequirePermission::class,
=======
            'contexto.oficina' => \App\Http\Middleware\EstablecerContextoOficina::class,
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
