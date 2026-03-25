<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Http\Controllers\Api\V1\DataResourceController;
use App\Http\Controllers\Api\V1\Demo\DemoAuditController;
use App\Http\Controllers\Api\V1\Demo\DemoFileController;
use App\Http\Controllers\Api\V1\Demo\DemoJobController;
use App\Http\Controllers\Api\V1\Demo\DemoNotificationController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\ModuleSettingController;
use App\Http\Controllers\Api\V1\MetricsOverviewController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OpenApiController;
use App\Http\Controllers\Api\V1\OperationsOverviewController;
use App\Http\Controllers\Api\V1\ErrorLogController;
use App\Http\Controllers\Api\V1\SecurityLogController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\UserManagementController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\WebhookReceiverController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthCheckController::class);
    Route::get('/openapi.json', OpenApiController::class);

    Route::prefix('auth')->group(function (): void {
        Route::middleware('throttle:auth-api')->group(function (): void {
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
            Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        });

        Route::middleware(['auth-token', 'tenant-context'])->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::patch('/active-organization', [AuthController::class, 'switchActiveOrganization']);
            Route::patch('/active-work-assignment', [AuthController::class, 'switchActiveWorkAssignment']);
        });
    });

    Route::middleware(['auth-token', 'tenant-context'])->group(function (): void {
        Route::get('/data/resources', [DataResourceController::class, 'resources']);
        Route::get('/data/{resourceKey}', [DataResourceController::class, 'index']);
        Route::post('/data/{resourceKey}', [DataResourceController::class, 'store'])->middleware('throttle:data-writes');
        Route::get('/data/{resourceKey}/export', [DataResourceController::class, 'export']);
        Route::post('/data/{resourceKey}/import', [DataResourceController::class, 'import'])->middleware('throttle:data-writes');
        Route::get('/data/{resourceKey}/transfers', [DataResourceController::class, 'transfers']);
        Route::get('/data/transfers/{transferRun}/download', [DataResourceController::class, 'downloadTransfer'])
            ->middleware('throttle:downloads')
            ->name('api.v1.data.transfers.download');
        Route::get('/data/{resourceKey}/{recordId}', [DataResourceController::class, 'show']);
        Route::match(['put', 'patch'], '/data/{resourceKey}/{recordId}', [DataResourceController::class, 'update'])->middleware('throttle:data-writes');
        Route::delete('/data/{resourceKey}/{recordId}', [DataResourceController::class, 'destroy'])->middleware('throttle:data-writes');

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('/settings/bootstrap', [SettingController::class, 'bootstrap']);
        Route::get('/settings/me', [SettingController::class, 'me']);
        Route::patch('/settings/me', [SettingController::class, 'updateMe']);
        Route::get('/auth/api-tokens', [ApiTokenController::class, 'index']);
        Route::post('/auth/api-tokens', [ApiTokenController::class, 'store'])->middleware('throttle:data-writes');
        Route::delete('/auth/api-tokens/{tokenId}', [ApiTokenController::class, 'destroy']);

        Route::prefix('demo')->group(function (): void {
            Route::get('/audit', [DemoAuditController::class, 'index']);
            Route::get('/files', [DemoFileController::class, 'index']);
            Route::post('/files', [DemoFileController::class, 'store']);
            Route::get('/files/downloads', [DemoFileController::class, 'downloads']);
            Route::get('/files/{file}/download', [DemoFileController::class, 'download'])->middleware('throttle:downloads');
            Route::post('/files/{file}/temporary-link', [DemoFileController::class, 'temporaryLink']);
            Route::get('/jobs', [DemoJobController::class, 'index']);
            Route::post('/jobs', [DemoJobController::class, 'store']);
            Route::post('/notifications', [DemoNotificationController::class, 'store']);
        });

        Route::get('/modules', [ModuleController::class, 'index']);

        Route::middleware('permission:users.impersonate')->post('/auth/impersonate/{user}', [UserManagementController::class, 'impersonate']);
        Route::post('/auth/impersonation/leave', [UserManagementController::class, 'leaveImpersonation']);

        Route::middleware('permission:users.manage_roles')->group(function (): void {
            Route::get('/users', [UserManagementController::class, 'index']);
            Route::post('/users', [UserManagementController::class, 'store'])->middleware('throttle:data-writes');
            Route::patch('/users/{user}', [UserManagementController::class, 'update'])->middleware('throttle:data-writes');
            Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus'])->middleware('throttle:data-writes');
            Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->middleware('throttle:data-writes');
            Route::patch('/users/{user}/roles', [UserManagementController::class, 'updateRoles']);
        });

        Route::middleware('permission:security.manage')->get('/security/logs', [SecurityLogController::class, 'index']);
        Route::middleware('permission:security.manage')->get('/error-logs', [ErrorLogController::class, 'index']);
        Route::middleware('permission:security.manage')->get('/operations/overview', OperationsOverviewController::class);
        Route::middleware('permission:security.manage')->get('/metrics/overview', MetricsOverviewController::class);

        Route::middleware('permission:integrations.manage')->group(function (): void {
            Route::get('/webhooks/endpoints', [WebhookController::class, 'endpoints']);
            Route::post('/webhooks/endpoints', [WebhookController::class, 'store'])->middleware('throttle:data-writes');
            Route::patch('/webhooks/endpoints/{endpoint}', [WebhookController::class, 'update'])->middleware('throttle:data-writes');
            Route::post('/webhooks/endpoints/{endpoint}/test', [WebhookController::class, 'test'])->middleware('throttle:data-writes');
            Route::get('/webhooks/deliveries', [WebhookController::class, 'deliveries']);
            Route::get('/webhooks/receivers', [WebhookReceiverController::class, 'index']);
            Route::post('/webhooks/receivers', [WebhookReceiverController::class, 'store'])->middleware('throttle:data-writes');
            Route::patch('/webhooks/receivers/{receiver}', [WebhookReceiverController::class, 'update'])->middleware('throttle:data-writes');
            Route::get('/webhooks/receipts', [WebhookReceiverController::class, 'receipts']);
        });

        Route::middleware('permission:modules.manage')->group(function (): void {
            Route::patch('/modules/{moduleKey}', [ModuleController::class, 'updateStatus']);
            Route::get('/modules/{moduleKey}/settings', [ModuleSettingController::class, 'show']);
            Route::patch('/modules/{moduleKey}/settings', [ModuleSettingController::class, 'update']);
        });

        Route::middleware('permission:settings.manage')->group(function (): void {
            Route::get('/settings/global', [SettingController::class, 'global']);
            Route::patch('/settings/global', [SettingController::class, 'updateGlobal']);
            Route::get('/settings/organization', [SettingController::class, 'organization']);
            Route::patch('/settings/organization', [SettingController::class, 'updateOrganization']);
        });
    });

    Route::middleware('signed')->get(
        '/demo/files/{file}/temporary-download',
        [DemoFileController::class, 'temporaryDownload'],
    )->middleware('throttle:downloads')->name('api.v1.demo.files.temporary-download');

    Route::post('/webhooks/incoming/{receiver}', [WebhookReceiverController::class, 'receive'])
        ->middleware('throttle:data-writes')
        ->name('api.v1.webhooks.incoming.receive');
});
