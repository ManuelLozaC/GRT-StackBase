<?php

namespace App\Core\Scaffolding\Services;

use App\Core\Scaffolding\Support\ScaffoldNaming;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class DataResourceScaffoldGenerator
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
    }

    public function generate(string $moduleKey, string $resourceName, string $modelClass, ?string $permissionKey = null, bool $searchable = false, bool $force = false): array
    {
        $modules = config('modules.installed', []);
        $moduleConfig = $modules[$moduleKey] ?? null;

        if (! is_array($moduleConfig)) {
            throw new RuntimeException("El modulo {$moduleKey} no existe en la metadata cargada.");
        }

        $resource = ScaffoldNaming::resource($resourceName);
        $moduleStudly = Str::studly(Str::replace('-', ' ', $moduleKey));
        $resourcePath = rtrim((string) config('scaffolding.backend_modules_path'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$moduleStudly
            .DIRECTORY_SEPARATOR.'DataResources'
            .DIRECTORY_SEPARATOR.$resource['key'].'.php';

        if ($this->files->exists($resourcePath) && ! $force) {
            throw new RuntimeException("El recurso {$resource['key']} ya existe en {$resourcePath}. Usa --force para sobrescribir.");
        }

        $this->files->ensureDirectoryExists(dirname($resourcePath));
        $this->files->put(
            $resourcePath,
            $this->resourceStub($moduleKey, $moduleConfig['name'] ?? $moduleKey, $resource, $modelClass, $permissionKey, $searchable),
        );

        return [
            'resource' => $resource,
            'created' => [$resourcePath],
        ];
    }

    private function resourceStub(string $moduleKey, string $moduleName, array $resource, string $modelClass, ?string $permissionKey, bool $searchable): string
    {
        $permissionKey ??= "{$moduleKey}.manage";
        $searchBlock = $searchable ? "        'search' => [\n            'engine' => 'meilisearch',\n        ],\n" : '';
        $modelImport = ltrim($modelClass, '\\');
        $modelShort = class_basename($modelImport);

        return <<<PHP
<?php

use {$modelImport};

return [
    '{$resource['key']}' => [
        'name' => '{$resource['label']}',
        'description' => 'Recurso {$resource['label']} del modulo {$moduleName}. Scaffold controlado para completar manualmente.',
        'source_module' => '{$moduleKey}',
        'permission_key' => '{$permissionKey}',
        'model' => {$modelShort}::class,
{$searchBlock}        'default_sort' => [
            'field' => 'id',
            'direction' => 'desc',
        ],
        'capabilities' => [
            'create' => true,
            'update' => true,
            'delete' => true,
            'export' => true,
            'import' => false,
            'duplicate' => false,
        ],
        'fields' => [
            [
                'key' => 'id',
                'label' => 'ID',
                'type' => 'text',
                'table' => true,
                'form' => false,
                'sortable' => true,
            ],
            [
                'key' => 'nombre',
                'label' => 'Nombre',
                'type' => 'text',
                'rules' => ['required', 'string', 'max:140'],
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'descripcion',
                'label' => 'Descripcion',
                'type' => 'textarea',
                'rules' => ['nullable', 'string'],
                'table' => false,
            ],
            [
                'key' => 'activo',
                'label' => 'Activo',
                'type' => 'boolean',
                'rules' => ['nullable', 'boolean'],
                'sortable' => true,
                'filterable' => true,
            ],
        ],
    ],
];
PHP;
    }
}
