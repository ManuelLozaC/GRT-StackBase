<?php

namespace App\Core\Modules;

use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            ->map(fn (array $module, string $key): array => $this->normalizeModule($key, $module))
            ->keyBy('key');

        if (! $this->canUsePersistence()) {
            return $defaults->values();
        }

        $this->syncDefaults();

        $persisted = $this->modulesTableQuery()
            ->get()
            ->map(fn (object $module): array => $this->mapPersistedModule($module))
            ->keyBy('key');

        $catalog = $defaults
            ->map(fn (array $module, string $key): array => array_merge(
                $module,
                $persisted->get($key, []),
            ))
            ->merge(
                $persisted->except($defaults->keys()),
            );

        return $catalog
            ->map(fn (array $module): array => $this->decorateModule($module, $catalog))
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
        $catalog = $this->all()->keyBy('key');
        $module = $catalog->get($key);

        if ($module === null) {
            return null;
        }

        if ($enabled) {
            $this->assertCanBeEnabled($module);
        } else {
            $this->assertCanBeDisabled($module);
        }

        if (! $this->canUsePersistence()) {
            return array_merge($module, [
                'enabled' => $enabled,
            ]);
        }

        $payload = [
            'name' => $module['name'] ?? $key,
            'description' => $module['description'] ?? null,
            'version' => $module['version'] ?? null,
            'provider' => $module['provider'] ?? null,
            'is_enabled' => $enabled,
            'is_demo' => (bool) ($module['is_demo'] ?? false),
        ];

        $existing = $this->modulesTableQuery()
            ->where('key', $key)
            ->first();

        if ($existing) {
            $this->modulesTableQuery()
                ->where('key', $key)
                ->update($payload);
        } else {
            $this->modulesTableQuery()->insert(array_merge([
                'key' => $key,
            ], $payload, $this->timestampsPayload()));
        }

        return $this->all()
            ->firstWhere('key', $key);
    }

    protected function syncDefaults(): void
    {
        foreach ($this->modules as $key => $module) {
            $payload = [
                'name' => $module['name'] ?? $key,
                'description' => $module['description'] ?? null,
                'version' => $module['version'] ?? null,
                'provider' => $module['provider'] ?? null,
                'is_demo' => (bool) ($module['is_demo'] ?? false),
            ];

            $existing = $this->modulesTableQuery()
                ->where('key', $key)
                ->first();

            if ($existing) {
                $this->modulesTableQuery()
                    ->where('key', $key)
                    ->update(array_merge($payload, [
                        'updated_at' => now(),
                    ]));

                continue;
            }

            $this->modulesTableQuery()->insert(array_merge([
                'key' => $key,
                'is_enabled' => (bool) ($module['enabled'] ?? false),
            ], $payload, $this->timestampsPayload()));
        }
    }

    protected function modulesTableQuery()
    {
        return DB::table('system_modules');
    }

    protected function mapPersistedModule(object $module): array
    {
        return [
            'key' => $module->key,
            'name' => $module->name,
            'description' => $module->description,
            'version' => $module->version,
            'provider' => $module->provider,
            'enabled' => (bool) $module->is_enabled,
            'is_demo' => (bool) $module->is_demo,
        ];
    }

    protected function timestampsPayload(): array
    {
        $timestamp = now();

        return [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    protected function normalizeModule(string $key, array $module): array
    {
        return array_merge([
            'key' => $key,
            'enabled' => false,
            'protected' => false,
            'dependencies' => [],
            'permissions' => [],
            'settings' => [],
            'features' => [],
            'jobs' => [],
            'webhooks' => [],
            'dashboards' => [],
            'seeders' => [],
            'assets' => [],
            'frontend' => [
                'navigation' => null,
                'routes' => [],
            ],
        ], $module);
    }

    protected function decorateModule(array $module, Collection $catalog): array
    {
        $dependencyKeys = collect($module['dependencies'] ?? [])->values();
        $missingDependencies = $dependencyKeys
            ->reject(fn (string $dependencyKey): bool => $catalog->has($dependencyKey))
            ->values()
            ->all();
        $disabledDependencies = $dependencyKeys
            ->filter(fn (string $dependencyKey): bool => $catalog->has($dependencyKey))
            ->filter(fn (string $dependencyKey): bool => ! (bool) ($catalog->get($dependencyKey)['enabled'] ?? false))
            ->values()
            ->all();
        $blockingDependents = $catalog
            ->filter(function (array $candidate, string $candidateKey) use ($module): bool {
                return $candidateKey !== $module['key']
                    && (bool) ($candidate['enabled'] ?? false)
                    && in_array($module['key'], $candidate['dependencies'] ?? [], true);
            })
            ->keys()
            ->values()
            ->all();

        return array_merge($module, [
            'is_protected' => (bool) ($module['protected'] ?? false),
            'dependency_status' => [
                'missing' => $missingDependencies,
                'disabled' => $disabledDependencies,
                'ready' => $missingDependencies === [] && $disabledDependencies === [],
            ],
            'blocking_dependents' => $blockingDependents,
            'can_enable' => $missingDependencies === [] && $disabledDependencies === [],
            'can_disable' => ! (bool) ($module['protected'] ?? false) && $blockingDependents === [],
        ]);
    }

    protected function assertCanBeEnabled(array $module): void
    {
        $missingDependencies = $module['dependency_status']['missing'] ?? [];
        $disabledDependencies = $module['dependency_status']['disabled'] ?? [];

        if ($missingDependencies !== []) {
            throw new DomainException(sprintf(
                'No se puede habilitar el modulo porque faltan dependencias: %s.',
                implode(', ', $missingDependencies),
            ));
        }

        if ($disabledDependencies !== []) {
            throw new DomainException(sprintf(
                'No se puede habilitar el modulo porque requiere dependencias activas: %s.',
                implode(', ', $disabledDependencies),
            ));
        }
    }

    protected function assertCanBeDisabled(array $module): void
    {
        if ((bool) ($module['is_protected'] ?? false)) {
            throw new DomainException('No se puede deshabilitar un modulo protegido del core.');
        }

        $blockingDependents = $module['blocking_dependents'] ?? [];

        if ($blockingDependents !== []) {
            throw new DomainException(sprintf(
                'No se puede deshabilitar el modulo mientras existan dependientes activos: %s.',
                implode(', ', $blockingDependents),
            ));
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
