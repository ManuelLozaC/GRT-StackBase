<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Errors\Models\CoreErrorLog;
use App\Core\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ErrorLogController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $logs = CoreErrorLog::query()
            ->with('actor:id,name,email')
            ->latest('id')
            ->limit(100)
            ->get();

        return $this->successResponse(
            data: $logs->map(fn (CoreErrorLog $log): array => [
                'id' => $log->id,
                'error_code' => $log->error_code,
                'error_class' => $log->error_class,
                'message' => $log->message,
                'request_id' => $log->request_id,
                'ip_address' => $log->ip_address,
                'file_path' => $log->file_path,
                'line_number' => $log->line_number,
                'context' => $log->context,
                'occurred_at' => $log->occurred_at?->toIso8601String(),
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
            ])->all(),
            message: 'Error logs listados',
            meta: [
                'total' => $logs->count(),
            ],
        );
    }
}
