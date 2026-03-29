<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\ApiResponse;
use App\Core\Modules\ModuleRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthCheckController extends Controller
{
    use ApiResponse;

    public function __invoke(ModuleRegistry $modules): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'mail' => $this->checkMail(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];

        return $this->successResponse(
            data: [
                'app' => config('app.name'),
                'environment' => app()->environment(),
                'timestamp' => now()->toIso8601String(),
                'checks' => $checks,
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

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            return ['status' => 'error', 'detail' => $exception->getMessage()];
        }
    }

    protected function checkRedis(): array
    {
        try {
            $pong = Redis::connection()->ping();

            return ['status' => str_contains(strtolower((string) $pong), 'pong') ? 'ok' : 'warning'];
        } catch (Throwable $exception) {
            return ['status' => 'error', 'detail' => $exception->getMessage()];
        }
    }

    protected function checkMail(): array
    {
        $configured = filled(config('mail.from.address'));
        $mailer = (string) config('mail.default');

        if ($mailer === 'resend') {
            $configured = $configured && filled(config('services.resend.key'));
        }

        return [
            'status' => $configured ? 'ok' : 'warning',
            'mailer' => $mailer,
        ];
    }

    protected function checkQueue(): array
    {
        return [
            'status' => 'ok',
            'connection' => config('queue.default'),
        ];
    }

    protected function checkStorage(): array
    {
        return [
            'status' => 'ok',
            'disk' => config('filesystems.default'),
            'fallback' => config('filesystems.fallback_disk'),
        ];
    }
}
