<?php

namespace App\Jobs\Demo;

use App\Core\Jobs\Models\CoreJobRun;
use App\Core\Jobs\Services\CoreJobRunner;
use App\Core\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessDemoJobRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15];

    public function __construct(
        public int $jobRunId,
    ) {
        $this->onQueue('demo');
    }

    public function handle(CoreJobRunner $jobRunner, TenantContext $tenantContext): void
    {
        $jobRun = CoreJobRun::query()->findOrFail($this->jobRunId);
        $attempts = $this->job?->attempts() ?? 1;

        $tenantContext->setOrganizationId($jobRun->organizacion_id);

        try {
            $jobRunner->runDemoJob($jobRun, $attempts);
        } finally {
            $tenantContext->clear();
        }
    }

    public function failed(Throwable $exception): void
    {
        $jobRun = CoreJobRun::query()->find($this->jobRunId);

        if ($jobRun !== null) {
            app(CoreJobRunner::class)->markFailed(
                $jobRun,
                $exception,
                $this->job?->attempts() ?? 1,
            );
        }
    }
}
