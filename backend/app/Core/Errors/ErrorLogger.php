<?php

namespace App\Core\Errors;

use App\Core\Errors\Models\CoreErrorLog;
use App\Core\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class ErrorLogger
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected Request $request,
    ) {
    }

    public function log(
        Throwable $exception,
        string $errorCode = 'internal_error',
        array $context = [],
        ?User $actor = null,
    ): CoreErrorLog {
        $resolvedOrganizationId = $this->tenantContext->companyId($actor);
        $resolvedActorId = $actor?->id ?? $this->tenantContext->actorId();

        return CoreErrorLog::query()->create([
            'organizacion_id' => $resolvedOrganizationId,
            'actor_id' => $resolvedActorId,
            'request_id' => $this->request->attributes->get('request_id'),
            'ip_address' => $this->request->ip(),
            'error_class' => $exception::class,
            'error_code' => $errorCode,
            'message' => $exception->getMessage(),
            'file_path' => $exception->getFile(),
            'line_number' => $exception->getLine(),
            'context' => $context,
            'trace_excerpt' => collect($exception->getTrace())
                ->take(5)
                ->map(fn (array $frame): array => [
                    'file' => $frame['file'] ?? null,
                    'line' => $frame['line'] ?? null,
                    'function' => $frame['function'] ?? null,
                    'class' => $frame['class'] ?? null,
                ])
                ->values()
                ->all(),
            'occurred_at' => now(),
        ]);
    }
}
