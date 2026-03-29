<?php

namespace App\Jobs\Demo;

use App\Core\Files\Services\FileManager;
use App\Core\Jobs\Models\CoreJobRun;
use App\Core\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessDemoFilePackageRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(
        public int $jobRunId,
    ) {
        $this->onQueue('files');
    }

    public function handle(FileManager $fileManager, TenantContext $tenantContext): void
    {
        $jobRun = CoreJobRun::query()->findOrFail($this->jobRunId);
        $tenantContext->setOrganizationId($jobRun->organizacion_id);
        $tenantContext->setActorId($jobRun->requested_by);

        try {
            $fileManager->processQueuedPackage(
                jobRun: $jobRun,
                attempts: $this->job?->attempts() ?? 1,
            );
        } finally {
            $tenantContext->clear();
        }
    }

    public function failed(Throwable $exception): void
    {
        $jobRun = CoreJobRun::query()->find($this->jobRunId);

        if ($jobRun !== null) {
            app(FileManager::class)->failQueuedPackage(
                jobRun: $jobRun,
                exception: $exception,
                attempts: $this->job?->attempts() ?? 1,
            );
        }
    }
}
