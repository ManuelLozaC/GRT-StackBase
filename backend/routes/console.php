<?php

use App\Core\Settings\Models\CoreSetting;
use App\Core\DataEngine\DataResourceRegistry;
use App\Core\DataEngine\Services\DataSearchManager;
use App\Core\Scaffolding\Services\DataResourceScaffoldGenerator;
use App\Core\Scaffolding\Services\ModuleScaffoldGenerator;
use App\Models\User;
use Database\Seeders\InstalacionBaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('settings:deduplicate', function () {
    $duplicates = CoreSetting::query()
        ->select('scope', 'organizacion_id', 'user_id', 'setting_key', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as total'))
        ->groupBy('scope', 'organizacion_id', 'user_id', 'setting_key')
        ->having('total', '>', 1)
        ->get();

    $deleted = 0;

    foreach ($duplicates as $duplicate) {
        $query = CoreSetting::query()
            ->where('scope', $duplicate->scope)
            ->where('setting_key', $duplicate->setting_key)
            ->where('id', '!=', $duplicate->keep_id);

        $duplicate->organizacion_id === null
            ? $query->whereNull('organizacion_id')
            : $query->where('organizacion_id', $duplicate->organizacion_id);

        $duplicate->user_id === null
            ? $query->whereNull('user_id')
            : $query->where('user_id', $duplicate->user_id);

        $deleted += $query->delete();
    }

    $this->info("Settings duplicados eliminados: {$deleted}");
})->purpose('Remove duplicated core settings while keeping the newest value');

Artisan::command('data:reindex-search {resourceKey?}', function (?string $resourceKey = null) {
    $registry = app(DataResourceRegistry::class);
    $search = app(DataSearchManager::class);

    $resources = $resourceKey
        ? collect([$registry->findConfigured($resourceKey)])->filter()
        : $registry->available(null, false)->filter(fn (array $resource): bool => $search->supportsSearch($resource));

    if ($resources->isEmpty()) {
        $this->warn('No hay recursos configurados para reindexacion.');
        return;
    }

    foreach ($resources as $resource) {
        $result = $search->reindex($resource, null);
        $this->info(sprintf(
            '[%s] documentos indexados: %d',
            $resource['key'],
            $result['documents_indexed'] ?? 0,
        ));
    }
})->purpose('Reindex configured Data Engine resources in Meilisearch');

Artisan::command('platform:ensure-bootstrap', function () {
    $bootstrapUserExists = User::query()
        ->where('email', 'mloza@grt.com.bo')
        ->orWhere('alias', 'mloza')
        ->exists();

    $this->call(RolePermissionSeeder::class);
    app(\App\Core\Modules\ModuleRegistry::class)->syncManifestToPersistence();

    if (! $bootstrapUserExists) {
        $this->call(InstalacionBaseSeeder::class);
        $this->info('Bootstrap inicial creado.');
        return;
    }

    $this->info('Bootstrap inicial ya existe. Solo se refrescaron roles y permisos base.');
})->purpose('Ensure bootstrap data exists without resetting production credentials');

Artisan::command('stackbase:make-module {name} {--force}', function (string $name) {
    $result = app(ModuleScaffoldGenerator::class)->generate(
        name: $name,
        force: (bool) $this->option('force'),
    );

    $this->info("Modulo generado: {$result['module']['label']} [{$result['module']['key']}]");

    foreach ($result['created'] as $path) {
        $this->line("  + {$path}");
    }

    foreach ($result['warnings'] as $warning) {
        $this->warn($warning);
    }

    $this->newLine();
    $this->comment('Siguiente paso recomendado: completar rutas frontend, documentar el flujo y agregar recursos Data Engine solo si el caso realmente encaja.');
})->purpose('Generate a minimal StackBase module scaffold without touching giant config files manually');

Artisan::command('stackbase:make-data-resource {moduleKey} {resourceKey} {modelClass} {--permission=} {--search} {--force}', function (string $moduleKey, string $resourceKey, string $modelClass) {
    $result = app(DataResourceScaffoldGenerator::class)->generate(
        moduleKey: $moduleKey,
        resourceName: $resourceKey,
        modelClass: $modelClass,
        permissionKey: $this->option('permission') ?: null,
        searchable: (bool) $this->option('search'),
        force: (bool) $this->option('force'),
    );

    $this->info("Recurso Data Engine generado: {$result['resource']['label']} [{$result['resource']['key']}]");

    foreach ($result['created'] as $path) {
        $this->line("  + {$path}");
    }

    $this->newLine();
    $this->comment('El scaffold es intencionalmente conservador: revisa campos, reglas, relaciones y permiso antes de usarlo en produccion.');
})->purpose('Generate a controlled Data Engine resource scaffold for an existing module');
