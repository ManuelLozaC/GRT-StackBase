<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DataResourceController;
use App\Http\Controllers\Api\V1\Demo\DemoAuditController;
use App\Http\Controllers\Api\V1\Demo\DemoFileController;
use App\Http\Controllers\Api\V1\Demo\DemoJobController;
use App\Http\Controllers\Api\V1\Demo\DemoNotificationController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthCheckController::class);

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware(['auth-token', 'tenant-context'])->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::patch('/active-organization', [AuthController::class, 'switchActiveOrganization']);
        });
    });

    Route::middleware(['auth-token', 'tenant-context'])->group(function (): void {
        Route::get('/data/resources', [DataResourceController::class, 'resources']);
        Route::get('/data/{resourceKey}', [DataResourceController::class, 'index']);
        Route::post('/data/{resourceKey}', [DataResourceController::class, 'store']);
        Route::get('/data/{resourceKey}/{recordId}', [DataResourceController::class, 'show']);
        Route::match(['put', 'patch'], '/data/{resourceKey}/{recordId}', [DataResourceController::class, 'update']);
        Route::delete('/data/{resourceKey}/{recordId}', [DataResourceController::class, 'destroy']);

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

        Route::get('/modules', [ModuleController::class, 'index']);

        Route::middleware('permission:modules.manage')->group(function (): void {
            Route::patch('/modules/{moduleKey}', [ModuleController::class, 'updateStatus']);
        });
    });

    Route::middleware('signed')->get(
        '/demo/files/{file}/temporary-download',
        [DemoFileController::class, 'temporaryDownload'],
    )->name('api.v1.demo.files.temporary-download');
});
