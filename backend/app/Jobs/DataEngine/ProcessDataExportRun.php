<?php

namespace App\Jobs\DataEngine;

use App\Core\DataEngine\DataResourceRegistry;
use App\Core\DataEngine\Models\CoreDataTransferRun;
use App\Core\DataEngine\Services\DataTransferManager;
use App\Core\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessDataExportRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(
        public int $transferRunId,
    ) {
        $this->onQueue('data-exports');
        $this->onConnection(config('queue.default') === 'sync' ? 'database' : config('queue.default'));
    }

    public function handle(
        DataTransferManager $transfers,
        DataResourceRegistry $resources,
        TenantContext $tenantContext,
    ): void {
        $run = CoreDataTransferRun::query()->withoutGlobalScopes()->findOrFail($this->transferRunId);
        $resource = $resources->findConfigured($run->resource_key);

        if ($resource === null) {
            throw new \RuntimeException('El recurso del Data Engine ya no esta disponible para exportar.');
        }

        $tenantContext->setOrganizationId($run->organizacion_id);

        try {
            $transfers->processQueuedExport($run, $resource);
        } finally {
            $tenantContext->clear();
        }
    }

    public function failed(Throwable $exception): void
    {
        $run = CoreDataTransferRun::query()->withoutGlobalScopes()->find($this->transferRunId);

        if ($run !== null) {
            app(DataTransferManager::class)->failQueuedExport($run, $exception);
        }
    }
}
