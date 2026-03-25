<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Models\AuditLog;
use App\Core\DataEngine\Models\CoreDataTransferRun;
use App\Core\Files\Models\FileDownload;
use App\Core\Files\Models\ManagedFile;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Jobs\Models\CoreJobRun;
use App\Core\Notifications\Models\CoreNotification;
use App\Core\Security\Models\CoreSecurityLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OperationsOverviewController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        $windowStart = now()->subDay();

        $failedJobs = CoreJobRun::query()
            ->where('status', 'failed')
            ->where('failed_at', '>=', $windowStart)
            ->latest('failed_at')
            ->limit(5)
            ->get();

        $failedTransfers = CoreDataTransferRun::query()
            ->whereIn('status', ['failed', 'completed_with_errors'])
            ->where(function ($query) use ($windowStart): void {
                $query->where('finished_at', '>=', $windowStart)
                    ->orWhereNull('finished_at');
            })
            ->latest('id')
            ->limit(5)
            ->get();

        $securityEvents = CoreSecurityLog::query()
            ->with('actor:id,name,email')
            ->where('occurred_at', '>=', $windowStart)
            ->latest('occurred_at')
            ->limit(10)
            ->get();

        return $this->successResponse(
            data: [
                'summary' => [
                    'files' => [
                        'total' => ManagedFile::query()->count(),
                        'downloads_last_24h' => FileDownload::query()
                            ->where('downloaded_at', '>=', $windowStart)
                            ->count(),
                    ],
                    'jobs' => [
                        'pending' => CoreJobRun::query()->whereIn('status', ['pending', 'processing'])->count(),
                        'failed_last_24h' => CoreJobRun::query()
                            ->where('status', 'failed')
                            ->where('failed_at', '>=', $windowStart)
                            ->count(),
                        'completed_last_24h' => CoreJobRun::query()
                            ->where('status', 'completed')
                            ->where('finished_at', '>=', $windowStart)
                            ->count(),
                    ],
                    'transfers' => [
                        'processing' => CoreDataTransferRun::query()
                            ->where('status', 'processing')
                            ->count(),
                        'failed_last_24h' => CoreDataTransferRun::query()
                            ->whereIn('status', ['failed', 'completed_with_errors'])
                            ->where('finished_at', '>=', $windowStart)
                            ->count(),
                        'completed_last_24h' => CoreDataTransferRun::query()
                            ->where('status', 'completed')
                            ->where('finished_at', '>=', $windowStart)
                            ->count(),
                    ],
                    'notifications' => [
                        'unread' => CoreNotification::query()->whereNull('read_at')->count(),
                        'sent_last_24h' => CoreNotification::query()
                            ->where('created_at', '>=', $windowStart)
                            ->count(),
                    ],
                    'security' => [
                        'events_last_24h' => CoreSecurityLog::query()
                            ->where('occurred_at', '>=', $windowStart)
                            ->count(),
                        'warnings_last_24h' => CoreSecurityLog::query()
                            ->where('occurred_at', '>=', $windowStart)
                            ->where('severity', 'warning')
                            ->count(),
                    ],
                    'audit_events_last_24h' => AuditLog::query()
                        ->where('occurred_at', '>=', $windowStart)
                        ->count(),
                ],
                'recent_failed_jobs' => $failedJobs->map(fn (CoreJobRun $jobRun): array => [
                    'id' => $jobRun->uuid,
                    'job_key' => $jobRun->job_key,
                    'queue' => $jobRun->queue,
                    'attempts' => $jobRun->attempts,
                    'error_message' => $jobRun->error_message,
                    'failed_at' => $jobRun->failed_at?->toIso8601String(),
                ])->all(),
                'recent_failed_transfers' => $failedTransfers->map(fn (CoreDataTransferRun $transferRun): array => [
                    'id' => $transferRun->uuid,
                    'resource_key' => $transferRun->resource_key,
                    'type' => $transferRun->type,
                    'status' => $transferRun->status,
                    'records_total' => $transferRun->records_total,
                    'records_processed' => $transferRun->records_processed,
                    'records_failed' => $transferRun->records_failed,
                    'error_summary' => $transferRun->error_summary,
                    'finished_at' => $transferRun->finished_at?->toIso8601String(),
                ])->all(),
                'recent_security_events' => $securityEvents->map(fn (CoreSecurityLog $log): array => [
                    'id' => $log->id,
                    'event_key' => $log->event_key,
                    'severity' => $log->severity,
                    'summary' => $log->summary,
                    'request_id' => $log->request_id,
                    'actor' => $log->actor ? [
                        'id' => $log->actor->id,
                        'name' => $log->actor->name,
                        'email' => $log->actor->email,
                    ] : null,
                    'occurred_at' => $log->occurred_at?->toIso8601String(),
                ])->all(),
            ],
            message: 'Resumen operativo generado',
            meta: [
                'generated_at' => now()->toIso8601String(),
                'window_hours' => 24,
            ],
        );
    }
}
