<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\Models\CoreMetricEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MetricsOverviewController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        $windowStart = now()->subDay();

        $byModule = CoreMetricEvent::query()
            ->select('module_key', DB::raw('COUNT(*) as total'))
            ->where('occurred_at', '>=', $windowStart)
            ->groupBy('module_key')
            ->orderByDesc('total')
            ->get();

        $byCategory = CoreMetricEvent::query()
            ->select('event_category', DB::raw('COUNT(*) as total'))
            ->where('occurred_at', '>=', $windowStart)
            ->groupBy('event_category')
            ->orderByDesc('total')
            ->get();

        $recentEvents = CoreMetricEvent::query()
            ->with('actor:id,name,email')
            ->where('occurred_at', '>=', $windowStart)
            ->latest('occurred_at')
            ->limit(20)
            ->get();

        $httpEvents = CoreMetricEvent::query()
            ->where('event_category', 'http')
            ->where('occurred_at', '>=', $windowStart)
            ->get();

        $durations = $httpEvents
            ->pluck('context')
            ->map(fn (mixed $context): int => (int) ($context['duration_ms'] ?? 0))
            ->filter(fn (int $duration): bool => $duration > 0)
            ->values();

        $averageResponseTime = $durations->isEmpty()
            ? 0
            : (int) round($durations->avg());

        $slowRequests = $httpEvents
            ->filter(fn (CoreMetricEvent $event): bool => (int) ($event->context['duration_ms'] ?? 0) >= 800)
            ->sortByDesc('occurred_at')
            ->values();

        $recentSlowRequests = $slowRequests
            ->take(10)
            ->values();

        return $this->successResponse(
            data: [
                'summary' => [
                    'events_last_24h' => CoreMetricEvent::query()
                        ->where('occurred_at', '>=', $windowStart)
                        ->count(),
                    'active_modules_last_24h' => $byModule->count(),
                    'active_categories_last_24h' => $byCategory->count(),
                    'average_response_time_ms' => $averageResponseTime,
                    'slow_requests_last_24h' => $slowRequests->count(),
                ],
                'by_module' => $byModule->map(fn ($row): array => [
                    'module_key' => $row->module_key,
                    'total' => (int) $row->total,
                ])->all(),
                'by_category' => $byCategory->map(fn ($row): array => [
                    'event_category' => $row->event_category,
                    'total' => (int) $row->total,
                ])->all(),
                'recent_events' => $recentEvents->map(fn (CoreMetricEvent $event): array => [
                    'id' => $event->id,
                    'module_key' => $event->module_key,
                    'event_key' => $event->event_key,
                    'event_category' => $event->event_category,
                    'request_id' => $event->request_id,
                    'context' => $event->context,
                    'occurred_at' => $event->occurred_at?->toIso8601String(),
                    'actor' => $event->actor ? [
                        'id' => $event->actor->id,
                        'name' => $event->actor->name,
                        'email' => $event->actor->email,
                    ] : null,
                ])->all(),
                'recent_slow_requests' => $recentSlowRequests->map(fn (CoreMetricEvent $event): array => [
                    'id' => $event->id,
                    'module_key' => $event->module_key,
                    'path' => $event->context['path'] ?? null,
                    'method' => $event->context['method'] ?? null,
                    'status' => $event->context['status'] ?? null,
                    'duration_ms' => $event->context['duration_ms'] ?? null,
                    'occurred_at' => $event->occurred_at?->toIso8601String(),
                ])->all(),
            ],
            message: 'Metricas operativas generadas',
            meta: [
                'window_hours' => 24,
            ],
        );
    }
}
