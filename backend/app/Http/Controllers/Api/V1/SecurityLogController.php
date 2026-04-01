<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\ApiResponse;
use App\Core\Security\Models\CoreSecurityLog;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = CoreSecurityLog::query()
            ->with('actor:id,name,email')
            ->latest('occurred_at');

        $this->applyFilters($query, $request);

        $limit = min(max((int) $request->integer('limit', 100), 1), 250);
        $logs = $query->limit($limit)->get();

        return $this->successResponse(
            data: $logs->map(fn (CoreSecurityLog $log): array => [
                'id' => $log->id,
                'event_key' => $log->event_key,
                'severity' => $log->severity,
                'ip_address' => $log->ip_address,
                'request_id' => $log->request_id,
                'summary' => $log->summary,
                'context' => $log->context,
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
                'occurred_at' => $log->occurred_at?->toIso8601String(),
            ])->all(),
            message: 'Security logs listados',
            meta: [
                'total' => $logs->count(),
                'limit' => $limit,
                'filters' => [
                    'q' => $request->string('q')->toString(),
                    'severity' => $request->string('severity')->toString(),
                    'event_key' => $request->string('event_key')->toString(),
                    'request_id' => $request->string('request_id')->toString(),
                ],
            ],
        );
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        $search = trim($request->string('q')->toString());
        $severity = trim($request->string('severity')->toString());
        $eventKey = trim($request->string('event_key')->toString());
        $requestId = trim($request->string('request_id')->toString());

        $query->when($search !== '', function (Builder $builder) use ($search): void {
            $builder->where(function (Builder $nested) use ($search): void {
                $nested
                    ->where('event_key', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('request_id', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('actor', function (Builder $actorQuery) use ($search): void {
                        $actorQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        });

        $query->when($severity !== '', fn (Builder $builder) => $builder->where('severity', $severity));
        $query->when($eventKey !== '', fn (Builder $builder) => $builder->where('event_key', $eventKey));
        $query->when($requestId !== '', fn (Builder $builder) => $builder->where('request_id', $requestId));
    }
}
