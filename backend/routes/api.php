<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/recuperar-password', [AuthController::class, 'enviarEnlaceRecuperacion']);

    Route::middleware(['auth:sanctum', 'contexto.oficina'])->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/usuarios', [UserController::class, 'index']);
        Route::post('/usuarios', [UserController::class, 'store']);
        Route::get('/usuarios/{user}', [UserController::class, 'show']);
        Route::put('/usuarios/{user}', [UserController::class, 'update']);
        Route::patch('/usuarios/{user}/resetear-password', [UserController::class, 'resetearPassword']);
    });
});
