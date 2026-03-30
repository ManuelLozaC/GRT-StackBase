<?php

namespace App\Http\Controllers\Api\V1\Demo;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Jobs\Models\CoreJobRun;
use App\Core\Jobs\Services\CoreJobRunner;
use App\Core\Jobs\Services\QueueRuntimeInspector;
use App\Core\Metrics\MetricsRecorder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Demo\DispatchDemoJobRequest;
use App\Jobs\Demo\ProcessDemoJobRun;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoJobController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CoreJobRunner $jobRunner,
        protected QueueRuntimeInspector $queueRuntime,
        protected AuditLogger $auditLogger,
        protected MetricsRecorder $metrics,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $runs = CoreJobRun::query()
            ->with(['requester:id,name'])
            ->latest('id')
            ->get();

        return $this->successResponse(
            data: $runs->map(fn (CoreJobRun $run): array => $this->transformRun($run))->all(),
            message: 'Jobs demo listados',
            meta: [
                'total' => $runs->count(),
                'queue_runtime' => $this->queueRuntime->inspect(['demo']),
            ],
        );
    }

    public function store(DispatchDemoJobRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $mode = $request->string('mode')->toString() ?: 'queued';
        $run = $this->jobRunner->createDemoRun(
            user: $user,
            payload: [
                'message' => $request->string('message')->toString(),
                'mode' => $mode,
                'should_fail' => $request->boolean('should_fail'),
            ],
        );

        $this->auditLogger->record(
            eventKey: 'demo.job.dispatched',
            actor: $user,
            entityType: 'core_job_run',
            entityKey: $run->uuid,
            summary: 'Se solicito un job de demo',
            sourceModule: 'demo-platform',
            context: [
                'mode' => $mode,
                'should_fail' => $request->boolean('should_fail'),
            ],
        );
        $this->metrics->record(
            moduleKey: 'demo-platform',
            eventKey: 'demo.job.dispatched',
            eventCategory: 'jobs',
            actor: $user,
            context: [
                'job_uuid' => $run->uuid,
                'mode' => $mode,
            ],
        );

        if ($mode === 'immediate') {
            $run = $this->jobRunner->runDemoJob($run);

            $this->auditLogger->record(
                eventKey: $run->status === 'failed'
                    ? 'demo.job.failed'
                    : 'demo.job.completed',
                actor: $user,
                entityType: 'core_job_run',
                entityKey: $run->uuid,
                summary: $run->status === 'failed'
                    ? 'El job de demo finalizo con fallo controlado'
                    : 'El job de demo finalizo correctamente',
                sourceModule: 'demo-platform',
                context: [
                    'mode' => $mode,
                    'status' => $run->status,
                ],
            );
            $this->metrics->record(
                moduleKey: 'demo-platform',
                eventKey: $run->status === 'failed' ? 'demo.job.failed' : 'demo.job.completed',
                eventCategory: 'jobs',
                actor: $user,
                context: [
                    'job_uuid' => $run->uuid,
                    'status' => $run->status,
                ],
            );

            return $this->successResponse(
                data: $this->transformRun($run->load('requester:id,name')),
                message: $run->status === 'failed'
                    ? 'Job demo ejecutado con fallo controlado'
                    : 'Job demo ejecutado inmediatamente',
                meta: [
                    'queue_runtime' => $this->queueRuntime->inspect(['demo']),
                ],
            );
        }

        ProcessDemoJobRun::dispatch($run->id);

        return $this->successResponse(
            data: $this->transformRun($run->load('requester:id,name')),
            message: 'Job demo enviado a cola',
            meta: [
                'worker_hint' => $this->queueRuntime->inspect(['demo'])['worker_hint'],
                'queue_runtime' => $this->queueRuntime->inspect(['demo']),
            ],
        );
    }

    public function retry(Request $request, CoreJobRun $jobRun): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! in_array($jobRun->status, ['failed', 'pending'], true)) {
            return $this->errorResponse(
                message: 'Solo se pueden reintentar jobs fallidos o pendientes.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $jobRun->forceFill([
            'status' => 'pending',
            'started_at' => null,
            'finished_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'result_payload' => null,
            'dispatched_at' => now(),
        ])->save();

        ProcessDemoJobRun::dispatch($jobRun->id);

        $this->auditLogger->record(
            eventKey: 'demo.job.retried',
            actor: $user,
            entityType: 'core_job_run',
            entityKey: $jobRun->uuid,
            summary: 'Se reintento un job de demo',
            sourceModule: 'demo-platform',
            context: [
                'job_uuid' => $jobRun->uuid,
                'attempts' => $jobRun->attempts,
            ],
        );
        $this->metrics->record(
            moduleKey: 'demo-platform',
            eventKey: 'demo.job.retried',
            eventCategory: 'jobs',
            actor: $user,
            context: [
                'job_uuid' => $jobRun->uuid,
            ],
        );

        return $this->successResponse(
            data: $this->transformRun($jobRun->fresh()->load('requester:id,name')),
            message: 'Job demo reenviado a cola',
            meta: [
                'worker_hint' => $this->queueRuntime->inspect(['demo'])['worker_hint'],
                'queue_runtime' => $this->queueRuntime->inspect(['demo']),
            ],
        );
    }

    protected function transformRun(CoreJobRun $run): array
    {
        return [
            'uuid' => $run->uuid,
            'job_key' => $run->job_key,
            'queue' => $run->queue,
            'status' => $run->status,
            'attempts' => $run->attempts,
            'requested_payload' => $run->requested_payload,
            'result_payload' => $run->result_payload,
            'error_message' => $run->error_message,
            'requested_by' => $run->requester?->name,
            'requested_by_id' => $run->requested_by,
            'organizacion_id' => $run->organizacion_id,
            'empresa_id' => $run->organizacion_id,
            'company_id' => $run->organizacion_id,
            'max_tries' => 3,
            'backoff_schedule' => [5, 15],
            'can_retry' => in_array($run->status, ['failed', 'pending'], true),
            'dispatched_at' => $run->dispatched_at?->toIso8601String(),
            'started_at' => $run->started_at?->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
            'failed_at' => $run->failed_at?->toIso8601String(),
        ];
    }
}
