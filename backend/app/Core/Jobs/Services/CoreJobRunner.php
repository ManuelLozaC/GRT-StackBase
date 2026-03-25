<?php

namespace App\Core\Jobs\Services;

use App\Core\Jobs\Models\CoreJobRun;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

class CoreJobRunner
{
    public function createDemoRun(User $user, array $payload, string $queue = 'demo'): CoreJobRun
    {
        return CoreJobRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'organizacion_id' => $user->organizacion_activa_id,
            'requested_by' => $user->id,
            'job_key' => 'demo.text-transform',
            'queue' => $queue,
            'status' => 'pending',
            'requested_payload' => $payload,
            'attempts' => 0,
            'dispatched_at' => now(),
        ]);
    }

    public function runDemoJob(CoreJobRun $jobRun, int $attempts = 1): CoreJobRun
    {
        if (in_array($jobRun->status, ['completed', 'failed'], true)) {
            return $jobRun;
        }

        $jobRun->forceFill([
            'status' => 'processing',
            'attempts' => $attempts,
            'started_at' => now(),
            'error_message' => null,
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
            ];

            $jobRun->forceFill([
                'status' => 'completed',
                'result_payload' => $result,
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
            'failed_at' => now(),
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
        ])->save();
    }
}
