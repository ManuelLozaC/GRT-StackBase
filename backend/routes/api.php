<?php

<<<<<<< HEAD
use App\Http\Controllers\Api\V1\Demo\DemoFileController;
use App\Http\Controllers\Api\V1\Demo\DemoJobController;
use App\Http\Controllers\Api\V1\Demo\DemoAuditController;
use App\Http\Controllers\Api\V1\Demo\DemoNotificationController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthCheckController::class);

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth-token')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::patch('/active-organization', [AuthController::class, 'switchActiveOrganization']);
        });
    });

    Route::middleware('auth-token')->group(function (): void {
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        Route::prefix('demo')->group(function (): void {
            Route::get('/audit', [DemoAuditController::class, 'index']);
            Route::get('/files', [DemoFileController::class, 'index']);
            Route::post('/files', [DemoFileController::class, 'store']);
            Route::get('/files/downloads', [DemoFileController::class, 'downloads']);
            Route::get('/files/{file}/download', [DemoFileController::class, 'download']);
            Route::post('/files/{file}/temporary-link', [DemoFileController::class, 'temporaryLink']);
            Route::get('/jobs', [DemoJobController::class, 'index']);
            Route::post('/jobs', [DemoJobController::class, 'store']);
            Route::post('/notifications', [DemoNotificationController::class, 'store']);
        });

        Route::middleware('permission:modules.manage')->group(function (): void {
            Route::get('/modules', [ModuleController::class, 'index']);
            Route::patch('/modules/{moduleKey}', [ModuleController::class, 'updateStatus']);
        });
    });

    Route::middleware('signed')->get(
        '/demo/files/{file}/temporary-download',
        [DemoFileController::class, 'temporaryDownload'],
    )->name('api.v1.demo.files.temporary-download');
=======
declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/recuperar-password', [AuthController::class, 'enviarEnlaceRecuperacion']);

    Route::middleware(['auth:sanctum', 'contexto.oficina'])->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/catalogos/formulario-usuarios', [CatalogoController::class, 'formularioUsuarios']);

        Route::get('/usuarios', [UserController::class, 'index']);
        Route::post('/usuarios', [UserController::class, 'store']);
        Route::get('/usuarios/{user}', [UserController::class, 'show']);
        Route::put('/usuarios/{user}', [UserController::class, 'update']);
        Route::patch('/usuarios/{user}/resetear-password', [UserController::class, 'resetearPassword']);
    });
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
});
