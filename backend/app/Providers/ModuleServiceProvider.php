<?php

namespace App\Providers;

use App\Core\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class, function (): ModuleRegistry {
            return new ModuleRegistry(
                config('modules.installed', []),
            );
        });

        foreach ($this->app->make(ModuleRegistry::class)->enabledProviders() as $provider) {
            $this->app->register($provider);
        }
    }
}
