<?php

namespace App\Http\Middleware;

use App\Core\Metrics\MetricsRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackApiPerformance
{
    public function __construct(
        protected MetricsRecorder $metrics,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $response->headers->set('X-Response-Time-ms', (string) $durationMs);

        if ($request->is('api/*') && ! $request->is('api/v1/health')) {
            $route = $request->route();
            $routeName = $route?->getName() ?: $route?->uri();
            $moduleKey = str_contains((string) $request->path(), '/demo/')
                ? 'demo-platform'
                : 'core-platform';

            $this->metrics->record(
                moduleKey: $moduleKey,
                eventKey: 'http.request.completed',
                eventCategory: 'http',
                actor: $request->user(),
                context: [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'route' => $routeName,
                    'status' => $response->getStatusCode(),
                    'duration_ms' => $durationMs,
                ],
            );
        }

        return $response;
    }
}
