<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\ApiResponse;
use App\Core\Security\Models\CoreSecurityLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SecurityLogController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $logs = CoreSecurityLog::query()
            ->with('actor:id,name,email')
            ->latest('id')
            ->limit(100)
            ->get();

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
            ],
        );
    }
}
