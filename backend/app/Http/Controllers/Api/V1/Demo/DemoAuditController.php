<?php

namespace App\Http\Controllers\Api\V1\Demo;

use App\Core\Audit\Models\AuditLog;
use App\Core\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoAuditController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::query()
            ->with(['actor:id,name,email'])
            ->latest('occurred_at')
            ->limit(100)
            ->get();

        return $this->successResponse(
            data: $logs->map(fn (AuditLog $log): array => $this->transformLog($log))->all(),
            message: 'Auditoria listada',
            meta: [
                'total' => $logs->count(),
            ],
        );
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
            'occurred_at' => $log->occurred_at?->toIso8601String(),
            'actor' => $log->actor === null
                ? null
                : [
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ],
        ];
    }
}
