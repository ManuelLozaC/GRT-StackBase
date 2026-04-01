<?php

namespace App\Core\Scaffolding\Services;

use App\Core\Scaffolding\Support\ScaffoldNaming;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class ModuleScaffoldGenerator
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
    }

    public function generate(string $name, bool $force = false): array
    {
        $module = ScaffoldNaming::module($name);

        $backendModulesPath = config('scaffolding.backend_modules_path');
        $frontendModulesPath = config('scaffolding.frontend_modules_path');
        $docsModulesPath = config('scaffolding.docs_modules_path');

        $backendModulePath = $backendModulesPath.DIRECTORY_SEPARATOR.$module['studly'];
        $frontendModulePath = $frontendModulesPath.DIRECTORY_SEPARATOR.$module['frontend_key'];
        $docsModulePath = $docsModulesPath.DIRECTORY_SEPARATOR.$module['key'].'.md';

        $this->ensureWritablePath($backendModulePath, $force);

        $created = [];
        $warnings = [];

        $this->files->ensureDirectoryExists($backendModulePath);
        $this->files->ensureDirectoryExists($backendModulePath.DIRECTORY_SEPARATOR.'DataResources');
        $this->files->ensureDirectoryExists($backendModulePath.DIRECTORY_SEPARATOR.'Events');
        $this->files->ensureDirectoryExists($backendModulePath.DIRECTORY_SEPARATOR.'Models');

        $this->putFile(
            $backendModulePath.DIRECTORY_SEPARATOR.$module['studly'].'ServiceProvider.php',
            $this->serviceProviderStub($module),
            $force,
            $created,
        );

        $this->putFile(
            $backendModulePath.DIRECTORY_SEPARATOR.'module.php',
            $this->moduleManifestStub($module),
            $force,
            $created,
        );

        if ($frontendModulesPath && ($this->files->isDirectory($frontendModulesPath) || $this->files->isDirectory(dirname($frontendModulePath)))) {
            $this->files->ensureDirectoryExists($frontendModulePath);
            $this->putFile(
                $frontendModulePath.DIRECTORY_SEPARATOR.'registry.js',
                $this->frontendRegistryStub($module),
                $force,
                $created,
            );
        } else {
            $warnings[] = 'No se genero el frontend del modulo porque la ruta de modulos frontend no existe en este entorno.';
        }

        if ($docsModulesPath && ($this->files->isDirectory($docsModulesPath) || $this->files->isDirectory(dirname($docsModulePath)))) {
            $this->files->ensureDirectoryExists(dirname($docsModulePath));
            $this->putFile(
                $docsModulePath,
                $this->moduleDocStub($module),
                $force,
                $created,
            );
        } else {
            $warnings[] = 'No se genero la documentacion del modulo porque la ruta de docs no existe en este entorno.';
        }

        return [
            'module' => $module,
            'created' => $created,
            'warnings' => $warnings,
        ];
    }

    private function ensureWritablePath(string $path, bool $force): void
    {
        if ($this->files->exists($path) && ! $force) {
            throw new RuntimeException("El modulo ya existe en {$path}. Usa --force solo si realmente quieres sobrescribir.");
        }
    }

    private function putFile(string $path, string $contents, bool $force, array &$created): void
    {
        if ($this->files->exists($path) && ! $force) {
            throw new RuntimeException("El archivo {$path} ya existe. Usa --force para sobrescribir.");
        }

        $this->files->put($path, $contents);
        $created[] = $path;
    }

    private function serviceProviderStub(array $module): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$module['studly']};

use Illuminate\Support\ServiceProvider;

class {$module['studly']}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
PHP;
    }

    private function moduleManifestStub(array $module): string
    {
        return <<<PHP
<?php

return [
    '{$module['key']}' => [
        'name' => '{$module['label']}',
        'description' => 'Modulo {$module['label']} generado como punto de partida controlado.',
        'version' => '0.1.0',
        'enabled' => false,
        'is_demo' => false,
        'protected' => false,
        'provider' => App\Modules\\{$module['studly']}\\{$module['studly']}ServiceProvider::class,
        'dependencies' => [
            'core-platform',
        ],
        'permissions' => [
            '{$module['key']}.view',
            '{$module['key']}.manage',
        ],
        'settings' => [],
        'features' => [],
        'jobs' => [],
        'webhooks' => [],
        'dashboards' => [],
        'seeders' => [],
        'assets' => [],
        'frontend' => [
            'navigation' => [
                'label' => '{$module['label']}',
            ],
            'routes' => [],
        ],
    ],
];
PHP;
    }

    private function frontendRegistryStub(array $module): string
    {
        return <<<JS
const registry = {
    navigation: {
        label: '{$module['label']}',
    },
    routes: [
        // Cada ruta debe declarar permissionKey explicita si el modulo tiene mas de un permiso.
        // {
        //     path: '/{$module['key']}',
        //     name: '{$module['key']}.index',
        //     view: '{$module['key']}.index',
        //     meta: {
        //         permissionKey: '{$module['key']}.view'
        //     },
        //     menu: {
        //         label: '{$module['label']}',
        //         icon: 'pi pi-fw pi-box'
        //     }
        // }
    ],
};

export default registry;
JS;
    }

    private function moduleDocStub(array $module): string
    {
        $moduleKey = $module['key'];

        return <<<MD
# Modulo {$module['label']}

## Objetivo
Describe aqui el problema de negocio que resuelve `{$moduleKey}`.

## Permisos base
- `{$moduleKey}.view`
- `{$moduleKey}.manage`

## Pasos sugeridos
1. definir entidades y reglas de negocio
2. registrar rutas frontend reales en `frontend/src/modules/{$module['frontend_key']}/registry.js`
3. declarar `permissionKey` explicita por ruta si el modulo tendra mas de un permiso
3. agregar recursos Data Engine solo si el caso realmente encaja
4. definir eventos de dominio y notificaciones del modulo
5. cubrir con tests y documentar el flujo
MD;
    }
}
