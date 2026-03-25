<?php

namespace App\Providers;

use App\Core\DataEngine\DataResourceRegistry;
use App\Core\DataEngine\Services\DataTransferManager;
use App\Core\Modules\ModuleSettingsManager;
use App\Core\Settings\CoreSettingsManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DataResourceRegistry::class, function ($app): DataResourceRegistry {
            return new DataResourceRegistry(
                config('data_resources', []),
                $app->make(\App\Core\Modules\ModuleRegistry::class),
            );
        });

        $this->app->singleton(ModuleSettingsManager::class, function ($app): ModuleSettingsManager {
            return new ModuleSettingsManager(
                $app->make(\App\Core\Modules\ModuleRegistry::class),
            );
        });

        $this->app->singleton(DataTransferManager::class, function ($app): DataTransferManager {
            return new DataTransferManager(
                $app->make(\App\Core\Tenancy\TenantContext::class),
                $app->make(\App\Core\Audit\Services\AuditLogger::class),
            );
        });

        $this->app->singleton(CoreSettingsManager::class, function (): CoreSettingsManager {
            return new CoreSettingsManager(
                config('core_settings', []),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-api', function (Request $request): array {
            return [
                Limit::perMinute(10)->by($request->ip() ?: 'auth-api'),
            ];
        });

        RateLimiter::for('downloads', function (Request $request): array {
            $key = $request->user()?->id ?: ($request->ip() ?: 'downloads');

            return [
                Limit::perMinute(30)->by('downloads:'.$key),
            ];
        });

        RateLimiter::for('data-writes', function (Request $request): array {
            $key = $request->user()?->id ?: ($request->ip() ?: 'data-writes');

            return [
                Limit::perMinute(60)->by('data-writes:'.$key),
            ];
        });
    }
}
