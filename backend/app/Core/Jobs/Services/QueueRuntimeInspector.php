<?php

namespace App\Core\Jobs\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QueueRuntimeInspector
{
    public function inspect(?array $queues = null): array
    {
        $connection = (string) config('queue.default');
        $queues = $queues ?: ['default', 'demo', 'files', 'notifications', 'data-exports'];

        $summary = [
            'status' => 'ok',
            'connection' => $connection,
            'supports_pending_counts' => false,
            'pending_total' => null,
            'failed_total' => null,
            'queues' => [],
            'worker_hint' => $this->workerHint($queues),
        ];

        if ($connection !== 'database') {
            return array_merge($summary, [
                'status' => 'warning',
                'detail' => 'El driver actual no permite inspeccionar pendientes por tabla desde la aplicacion.',
            ]);
        }

        try {
            if (! Schema::hasTable('jobs')) {
                return array_merge($summary, [
                    'status' => 'warning',
                    'detail' => 'La tabla jobs no existe en este entorno.',
                ]);
            }

            $failedJobsTable = (string) config('queue.failed.table', 'failed_jobs');
            $hasFailedJobsTable = Schema::hasTable($failedJobsTable);

            $summary['supports_pending_counts'] = true;
            $summary['pending_total'] = (int) DB::table('jobs')->count();
            $summary['failed_total'] = $hasFailedJobsTable ? (int) DB::table($failedJobsTable)->count() : null;
            $summary['queues'] = collect($queues)->map(fn (string $queue): array => [
                'name' => $queue,
                'pending' => (int) DB::table('jobs')->where('queue', $queue)->count(),
                'failed' => $hasFailedJobsTable
                    ? (int) DB::table($failedJobsTable)->where('queue', $queue)->count()
                    : null,
            ])->all();

            return $summary;
        } catch (Throwable $exception) {
            return array_merge($summary, [
                'status' => 'error',
                'detail' => $exception->getMessage(),
            ]);
        }
    }

    protected function workerHint(array $queues): string
    {
        $queueList = implode(',', $queues);

        return sprintf(
            'Docker local: docker compose up -d worker scheduler. Manual: php artisan queue:work --queue=%s',
            $queueList,
        );
    }
}
