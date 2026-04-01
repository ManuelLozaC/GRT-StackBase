<?php

namespace App\Core\Jobs\Services;

use App\Core\Jobs\Models\CoreJobRun;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

class CoreJobRunner
{
    protected const DEMO_JOB_POLICY = [
        'key' => 'demo-fast-feedback',
        'label' => 'Demo Fast Feedback',
        'max_attempts' => 3,
        'backoff_schedule' => [5, 15],
    ];

    public function __construct(
        protected TenantContext $tenantContext,
    ) {
    }

    public function createDemoRun(User $user, array $payload, string $queue = 'demo'): CoreJobRun
    {
        return CoreJobRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'organizacion_id' => $this->tenantContext->companyId($user),
            'requested_by' => $user->id,
            'job_key' => 'demo.text-transform',
            'queue' => $queue,
            'status' => 'pending',
            'requested_payload' => $payload,
            'metadata' => $this->buildJobMetadata(),
            'attempts' => 0,
            'dispatched_at' => now(),
        ]);
    }

    public function runDemoJob(CoreJobRun $jobRun, int $attempts = 1): CoreJobRun
    {
        if (in_array($jobRun->status, ['completed', 'failed'], true)) {
            return $jobRun;
        }

        $jobRun->loadMissing('requester');

        $jobRun->forceFill([
            'status' => 'processing',
            'attempts' => $attempts,
            'started_at' => now(),
            'error_message' => null,
            'metadata' => $this->buildJobMetadata($jobRun, [
                'last_attempt_at' => now()->toIso8601String(),
                'retry_exhausted' => false,
                'retriable' => true,
                'next_retry_in_seconds' => $this->nextRetryInSeconds($attempts),
            ]),
        ])->save();

        try {
            $payload = $jobRun->requested_payload ?? [];
            $message = (string) ($payload['message'] ?? '');
            $normalized = trim($message);
            $shouldFail = (bool) ($payload['should_fail'] ?? false);

            if ($shouldFail) {
                throw new \RuntimeException('Fallo intencional de demo para validar reintentos y logs.');
            }

            $result = [
                'original_message' => $normalized,
                'uppercase_message' => Str::upper($normalized),
                'reversed_message' => Str::reverse($normalized),
                'word_count' => str_word_count($normalized),
                'processed_by' => 'demo.text-transform',
                'runtime_context' => $this->tenantContext->snapshot($jobRun->requester),
            ];

            $jobRun->forceFill([
                'status' => 'completed',
                'result_payload' => $result,
                'metadata' => $this->buildJobMetadata($jobRun, [
                    'last_attempt_at' => now()->toIso8601String(),
                    'retry_exhausted' => false,
                    'retriable' => false,
                    'next_retry_in_seconds' => null,
                ]),
                'finished_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();
        } catch (Throwable $exception) {
            $this->markFailed($jobRun, $exception, $attempts);
        }

        return $jobRun->fresh();
    }

    public function markFailed(CoreJobRun $jobRun, Throwable $exception, int $attempts = 1): void
    {
        $jobRun->forceFill([
            'status' => 'failed',
            'attempts' => $attempts,
            'metadata' => $this->buildJobMetadata($jobRun, [
                'last_attempt_at' => now()->toIso8601String(),
                'retry_exhausted' => $attempts >= $this->policy()['max_attempts'],
                'retriable' => $attempts < $this->policy()['max_attempts'],
                'next_retry_in_seconds' => $attempts >= $this->policy()['max_attempts'] ? null : $this->nextRetryInSeconds($attempts),
            ]),
            'failed_at' => now(),
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
        ])->save();
    }

    protected function buildJobMetadata(?CoreJobRun $jobRun = null, array $overrides = []): array
    {
        return array_merge(
            $jobRun->metadata ?? [],
            $this->policy(),
            [
                'retry_exhausted' => false,
                'retriable' => true,
                'next_retry_in_seconds' => null,
                'last_attempt_at' => null,
            ],
            $overrides,
        );
    }

    protected function policy(): array
    {
        return self::DEMO_JOB_POLICY;
    }

    protected function nextRetryInSeconds(int $attempts): ?int
    {
        return $this->policy()['backoff_schedule'][$attempts - 1] ?? null;
    }
}
