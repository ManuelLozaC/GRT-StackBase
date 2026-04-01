<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Errors\Models\CoreErrorLog;
use App\Core\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = CoreErrorLog::query()
            ->with('actor:id,name,email')
            ->latest('occurred_at');

        $this->applyFilters($query, $request);

        $limit = min(max((int) $request->integer('limit', 100), 1), 250);
        $logs = $query->limit($limit)->get();

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
                'limit' => $limit,
                'filters' => [
                    'q' => $request->string('q')->toString(),
                    'error_code' => $request->string('error_code')->toString(),
                    'request_id' => $request->string('request_id')->toString(),
                ],
            ],
        );
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        $search = trim($request->string('q')->toString());
        $errorCode = trim($request->string('error_code')->toString());
        $requestId = trim($request->string('request_id')->toString());

        $query->when($search !== '', function (Builder $builder) use ($search): void {
            $builder->where(function (Builder $nested) use ($search): void {
                $nested
                    ->where('error_code', 'like', "%{$search}%")
                    ->orWhere('error_class', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('request_id', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('file_path', 'like', "%{$search}%")
                    ->orWhereHas('actor', function (Builder $actorQuery) use ($search): void {
                        $actorQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        });

        $query->when($errorCode !== '', fn (Builder $builder) => $builder->where('error_code', $errorCode));
        $query->when($requestId !== '', fn (Builder $builder) => $builder->where('request_id', $requestId));
    }
}
