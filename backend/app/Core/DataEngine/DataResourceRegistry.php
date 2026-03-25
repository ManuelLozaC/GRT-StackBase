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
            ->map(fn (array $resource): array => $this->serializeDefinition($resource))
            ->all();
    }

    public function serializeDefinition(array $resource): array
    {
        return [
            'key' => $resource['key'],
            'name' => $resource['name'],
            'description' => $resource['description'],
            'source_module' => $resource['source_module'],
            'permission_key' => $resource['permission_key'],
            'default_sort' => $resource['default_sort'],
            'per_page_options' => $resource['per_page_options'],
            'capabilities' => $resource['capabilities'],
            'table_fields' => $resource['table_fields'],
            'form_fields' => $resource['form_fields'],
            'filter_fields' => $resource['filter_fields'],
            'custom_fields' => $resource['custom_fields'],
        ];
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

        return $user->can($permissionKey) || $this->contextPermissions->hasPermission($user, $permissionKey);
    }
}
