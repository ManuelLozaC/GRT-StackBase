<?php

namespace App\Core\Modules;

use App\Core\Modules\Models\SystemModule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ModuleRegistry
{
    public function __construct(
        protected array $modules,
    ) {
    }

    public function all(): Collection
    {
        $defaults = collect($this->modules)
            ->map(fn (array $module, string $key): array => array_merge([
                'key' => $key,
                'enabled' => false,
            ], $module))
            ->keyBy('key');

        if (! $this->canUsePersistence()) {
            return $defaults->values();
        }

        $this->syncDefaults();

        $persisted = SystemModule::query()
            ->get()
            ->map(fn (SystemModule $module): array => $module->toRegistryArray())
            ->keyBy('key');

        return $defaults
            ->map(fn (array $module, string $key): array => array_merge(
                $module,
                $persisted->get($key, []),
            ))
            ->merge(
                $persisted->except($defaults->keys()),
            )
            ->values();
    }

    public function enabled(): Collection
    {
        return $this->all()
            ->filter(fn (array $module): bool => (bool) ($module['enabled'] ?? false))
            ->values();
    }

    public function enabledProviders(): array
    {
        return $this->enabled()
            ->pluck('provider')
            ->filter(fn (mixed $provider): bool => is_string($provider) && class_exists($provider))
            ->values()
            ->all();
    }

    public function setEnabled(string $key, bool $enabled): ?array
    {
        $module = $this->all()
            ->firstWhere('key', $key);

        if ($module === null) {
            return null;
        }

        if (! $this->canUsePersistence()) {
            return array_merge($module, [
                'enabled' => $enabled,
            ]);
        }

        $record = SystemModule::query()->firstOrNew([
            'key' => $key,
        ]);

        $record->fill([
            'name' => $module['name'] ?? $key,
            'description' => $module['description'] ?? null,
            'version' => $module['version'] ?? null,
            'provider' => $module['provider'] ?? null,
            'is_enabled' => $enabled,
            'is_demo' => (bool) ($module['is_demo'] ?? false),
        ]);
        $record->save();

        return $record->toRegistryArray();
    }

    protected function syncDefaults(): void
    {
        foreach ($this->modules as $key => $module) {
            $record = SystemModule::query()->firstOrNew([
                'key' => $key,
            ]);

            $record->fill([
                'name' => $module['name'] ?? $key,
                'description' => $module['description'] ?? null,
                'version' => $module['version'] ?? null,
                'provider' => $module['provider'] ?? null,
                'is_demo' => (bool) ($module['is_demo'] ?? false),
            ]);

            if (! $record->exists) {
                $record->is_enabled = (bool) ($module['enabled'] ?? false);
            }

            $record->save();
        }
    }

    protected function canUsePersistence(): bool
    {
        try {
            return Schema::hasTable('system_modules');
        } catch (Throwable) {
            return false;
        }
    }
}
