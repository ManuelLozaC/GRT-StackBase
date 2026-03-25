<?php

namespace App\Providers;

use App\Core\DataEngine\DataResourceRegistry;
use App\Core\Modules\ModuleSettingsManager;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
