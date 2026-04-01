<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Models\AuditLog;
use App\Core\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()
            ->with('actor:id,name,email')
            ->latest('occurred_at');

        $this->applyFilters($query, $request);

        $limit = min(max((int) $request->integer('limit', 100), 1), 250);
        $logs = $query->limit($limit)->get();

        return $this->successResponse(
            data: $logs->map(fn (AuditLog $log): array => $this->transformLog($log))->all(),
            message: 'Auditoria listada',
            meta: [
                'total' => $logs->count(),
                'limit' => $limit,
                'filters' => [
                    'q' => $request->string('q')->toString(),
                    'event_key' => $request->string('event_key')->toString(),
                    'entity_type' => $request->string('entity_type')->toString(),
                    'source_module' => $request->string('source_module')->toString(),
                ],
            ],
        );
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        $search = trim($request->string('q')->toString());
        $eventKey = trim($request->string('event_key')->toString());
        $entityType = trim($request->string('entity_type')->toString());
        $sourceModule = trim($request->string('source_module')->toString());
        $requestId = trim($request->string('request_id')->toString());

        $query->when($search !== '', function (Builder $builder) use ($search): void {
            $builder->where(function (Builder $nested) use ($search): void {
                $nested
                    ->where('event_key', 'like', "%{$search}%")
                    ->orWhere('entity_type', 'like', "%{$search}%")
                    ->orWhere('entity_key', 'like', "%{$search}%")
                    ->orWhere('source_module', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('context->request_id', 'like', "%{$search}%")
                    ->orWhereHas('actor', function (Builder $actorQuery) use ($search): void {
                        $actorQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        });

        $query->when($eventKey !== '', fn (Builder $builder) => $builder->where('event_key', $eventKey));
        $query->when($entityType !== '', fn (Builder $builder) => $builder->where('entity_type', $entityType));
        $query->when($sourceModule !== '', fn (Builder $builder) => $builder->where('source_module', $sourceModule));
        $query->when($requestId !== '', fn (Builder $builder) => $builder->where('context->request_id', $requestId));
    }

    protected function transformLog(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'event_key' => $log->event_key,
            'entity_type' => $log->entity_type,
            'entity_key' => $log->entity_key,
            'source_module' => $log->source_module,
            'summary' => $log->summary,
            'context' => $log->context,
            'request_id' => $log->context['request_id'] ?? null,
            'occurred_at' => $log->occurred_at?->toIso8601String(),
            'actor' => $log->actor === null
                ? null
                : [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ],
        ];
    }
}
