<?php

namespace App\Core\DataEngine;

use App\Core\Auth\Services\ContextPermissionResolver;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Illuminate\Support\Collection;

class DataResourceRegistry
{
    public function __construct(
        protected array $resources,
        protected ModuleRegistry $modules,
        protected ContextPermissionResolver $contextPermissions,
    ) {
    }

    public function available(?User $user = null, bool $catalogOnly = false): Collection
    {
        return collect($this->resources)
            ->map(fn (array $resource, string $key): array => $this->normalizeResource($key, $resource))
            ->filter(fn (array $resource): bool => $this->isModuleEnabled($resource))
            ->filter(fn (array $resource): bool => $this->userCanAccess($resource, $user))
            ->filter(fn (array $resource): bool => ! $catalogOnly || (bool) ($resource['visible_in_catalog'] ?? true))
            ->values();
    }

    public function findAvailable(string $key, ?User $user = null, bool $catalogOnly = false): ?array
    {
        return $this->available($user, $catalogOnly)
            ->firstWhere('key', $key);
    }

    public function findConfigured(string $key): ?array
    {
        $resource = $this->modulesAreReady()
            ->get($key);

        return $resource ? $this->normalizeResource($key, $resource) : null;
    }

    public function toFrontendPayload(?User $user = null): array
    {
        return $this->available($user, true)
            ->map(fn (array $resource): array => $this->serializeDefinition($resource, $user))
            ->all();
    }

    public function serializeDefinition(array $resource, ?User $user = null): array
    {
        $capabilities = $this->resolveCapabilities($resource, $user);

        return [
            'key' => $resource['key'],
            'name' => $resource['name'],
            'description' => $resource['description'],
            'source_module' => $resource['source_module'],
            'permission_key' => $resource['permission_key'],
            'default_sort' => $resource['default_sort'],
            'per_page_options' => $resource['per_page_options'],
            'capabilities' => $capabilities,
            'table_fields' => $resource['table_fields'],
            'form_fields' => $resource['form_fields'],
            'filter_fields' => $resource['filter_fields'],
            'custom_fields' => $resource['custom_fields'],
            'record_actions' => array_merge($resource['record_actions'], [
                'duplicate' => $capabilities['duplicate'] ?? false,
            ]),
            'search' => array_merge($resource['search'], [
                'can_manage' => $this->userHasPermission($user, 'data-engine.search.manage'),
            ]),
        ];
    }

    public function userCanPerform(array $resource, ?User $user, string $action): bool
    {
        if (! $this->userCanAccess($resource, $user)) {
            return false;
        }

        if (! ($resource['capabilities'][$action] ?? false)) {
            return false;
        }

        $permissionKey = $resource['capability_permissions'][$action]
            ?? $this->defaultCapabilityPermission($action);

        if ($permissionKey === null || $user === null) {
            return $permissionKey === null;
        }

        return $user->can($permissionKey) || $this->contextPermissions->hasPermission($user, $permissionKey);
    }

    protected function normalizeResource(string $key, array $resource): array
    {
        $normalizedFields = collect($resource['fields'] ?? [])
            ->map(function (array $field): array {
                return array_merge([
                    'type' => 'text',
                    'table' => true,
                    'form' => true,
                    'importable' => true,
                    'exportable' => true,
                    'sortable' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'rules' => [],
                    'options' => [],
                    'relation' => null,
                ], $field);
            })
            ->values()
            ->all();
        $customFields = collect($resource['custom_fields'] ?? [])
            ->map(fn (array $field): array => array_merge([
                'type' => 'text',
                'table' => false,
                'form' => true,
                'filterable' => false,
                'searchable' => false,
                'importable' => true,
                'exportable' => true,
                'rules' => [],
                'options' => [],
            ], $field))
            ->values()
            ->all();

        return array_merge([
            'key' => $key,
            'name' => $key,
            'description' => null,
            'source_module' => 'core-platform',
            'permission_key' => null,
            'visible_in_catalog' => true,
            'default_sort' => [
                'field' => 'id',
                'direction' => 'desc',
            ],
            'per_page_options' => [10, 25, 50],
            'capabilities' => [
                'create' => true,
                'update' => true,
                'delete' => true,
                'export' => true,
                'import' => true,
                'duplicate' => false,
            ],
            'record_actions' => [
                'duplicate' => false,
            ],
            'capability_permissions' => [],
            'search' => [
                'engine' => config('search.default_engine', 'database'),
            ],
            'fields' => $normalizedFields,
            'custom_fields' => $customFields,
        ], $resource, [
            'fields' => $normalizedFields,
            'custom_fields' => $customFields,
            'table_fields' => collect($normalizedFields)->where('table', true)->values()->all(),
            'form_fields' => collect($normalizedFields)->where('form', true)->values()->all(),
            'filter_fields' => collect($normalizedFields)->where('filterable', true)->values()->all(),
            'searchable_fields' => collect($normalizedFields)->where('searchable', true)->pluck('key')->values()->all(),
            'sortable_fields' => collect($normalizedFields)->where('sortable', true)->pluck('key')->values()->all(),
            'relation_fields' => collect($normalizedFields)->whereNotNull('relation')->values()->all(),
            'searchable_custom_fields' => collect($customFields)->where('searchable', true)->values()->all(),
            'filterable_custom_fields' => collect($customFields)->where('filterable', true)->values()->all(),
            'exportable_custom_fields' => collect($customFields)->where('exportable', true)->values()->all(),
            'importable_custom_fields' => collect($customFields)->where('importable', true)->values()->all(),
        ]);
    }

    protected function modulesAreReady(): Collection
    {
        return collect($this->resources)
            ->filter(function (array $resource, string $key): bool {
                $normalized = $this->normalizeResource($key, $resource);

                return $this->isModuleEnabled($normalized);
            });
    }

    protected function isModuleEnabled(array $resource): bool
    {
        $moduleKey = $resource['source_module'] ?? 'core-platform';

        return $this->modules->all()
            ->contains(fn (array $module): bool => $module['key'] === $moduleKey && (bool) ($module['enabled'] ?? false));
    }

    protected function userCanAccess(array $resource, ?User $user): bool
    {
        $permissionKey = $resource['permission_key'] ?? null;

        if ($permissionKey === null || $user === null) {
            return true;
        }

        return $this->userHasPermission($user, $permissionKey);
    }

    protected function resolveCapabilities(array $resource, ?User $user): array
    {
        return collect($resource['capabilities'] ?? [])
            ->mapWithKeys(fn (bool $enabled, string $action): array => [
                $action => $enabled && $this->userCanPerform($resource, $user, $action),
            ])
            ->all();
    }

    protected function defaultCapabilityPermission(string $action): ?string
    {
        return [
            'create' => 'data-engine.create',
            'update' => 'data-engine.update',
            'delete' => 'data-engine.delete',
            'import' => 'data-engine.import',
            'export' => 'data-engine.export',
            'duplicate' => 'data-engine.duplicate',
        ][$action] ?? null;
    }

    public function userHasPermission(?User $user, string $permissionKey): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->can($permissionKey) || $this->contextPermissions->hasPermission($user, $permissionKey);
    }
}
