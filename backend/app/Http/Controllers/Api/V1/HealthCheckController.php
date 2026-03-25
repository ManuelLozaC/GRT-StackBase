<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\ApiResponse;
use App\Core\Modules\ModuleRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    use ApiResponse;

    public function __invoke(ModuleRegistry $modules): JsonResponse
    {
        return $this->successResponse(
            data: [
                'app' => config('app.name'),
                'environment' => app()->environment(),
                'timestamp' => now()->toIso8601String(),
                'modules' => $modules->enabled()->map(fn (array $module): array => [
                    'key' => $module['key'],
                    'name' => $module['name'] ?? $module['key'],
                    'version' => $module['version'] ?? null,
                ])->all(),
            ],
            message: 'API v1 operativa',
            meta: [
                'api_version' => 'v1',
            ],
        );
    }
}
