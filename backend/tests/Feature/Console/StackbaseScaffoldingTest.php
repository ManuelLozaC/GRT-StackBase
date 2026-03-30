<?php

namespace Tests\Feature\Console;

use App\Core\Scaffolding\Services\DataResourceScaffoldGenerator;
use App\Core\Scaffolding\Services\ModuleScaffoldGenerator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class StackbaseScaffoldingTest extends TestCase
{
    protected string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = storage_path('framework/testing/scaffolding/'.Str::uuid());

        config()->set('scaffolding.backend_modules_path', $this->tempRoot.'/backend-modules');
        config()->set('scaffolding.frontend_modules_path', $this->tempRoot.'/frontend-modules');
        config()->set('scaffolding.docs_modules_path', $this->tempRoot.'/docs-modules');

        File::ensureDirectoryExists(config('scaffolding.backend_modules_path'));
        File::ensureDirectoryExists(config('scaffolding.frontend_modules_path'));
        File::ensureDirectoryExists(config('scaffolding.docs_modules_path'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempRoot);

        parent::tearDown();
    }

    public function test_it_generates_a_minimal_module_scaffold(): void
    {
        app(ModuleScaffoldGenerator::class)->generate('Leads Pipeline');

        $backendModulePath = config('scaffolding.backend_modules_path').'/LeadsPipeline';

        $this->assertFileExists($backendModulePath.'/LeadsPipelineServiceProvider.php');
        $this->assertFileExists($backendModulePath.'/module.php');
        $this->assertFileExists(config('scaffolding.frontend_modules_path').'/leads-pipeline/registry.js');
        $this->assertFileExists(config('scaffolding.docs_modules_path').'/leads-pipeline.md');

        $manifest = require $backendModulePath.'/module.php';

        $this->assertArrayHasKey('leads-pipeline', $manifest);
        $this->assertSame('Leads Pipeline', $manifest['leads-pipeline']['name']);
        $this->assertSame(['leads-pipeline.view', 'leads-pipeline.manage'], $manifest['leads-pipeline']['permissions']);
    }

    public function test_it_generates_a_controlled_data_resource_scaffold(): void
    {
        $modulePath = config('scaffolding.backend_modules_path').'/LeadsPipeline';
        File::ensureDirectoryExists($modulePath.'/DataResources');

        config()->set('modules.installed.leads-pipeline', [
            'name' => 'Leads Pipeline',
        ]);

        app(DataResourceScaffoldGenerator::class)->generate(
            moduleKey: 'leads-pipeline',
            resourceName: 'Lead Card',
            modelClass: 'App\\Modules\\LeadsPipeline\\Models\\LeadCard',
            permissionKey: 'leads-pipeline.manage',
            searchable: true,
        );

        $resourcePath = $modulePath.'/DataResources/lead-card.php';

        $this->assertFileExists($resourcePath);

        $contents = File::get($resourcePath);

        $this->assertStringContainsString("'lead-card' => [", $contents);
        $this->assertStringContainsString("'source_module' => 'leads-pipeline'", $contents);
        $this->assertStringContainsString("'permission_key' => 'leads-pipeline.manage'", $contents);
        $this->assertStringContainsString("'engine' => 'meilisearch'", $contents);
    }

    public function test_artisan_commands_are_registered(): void
    {
        Artisan::call('list');

        $output = Artisan::output();

        $this->assertStringContainsString('stackbase:make-module', $output);
        $this->assertStringContainsString('stackbase:make-data-resource', $output);
    }
}
