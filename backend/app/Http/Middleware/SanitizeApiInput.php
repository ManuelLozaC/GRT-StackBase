<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeApiInput
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->merge($this->sanitizeArray($request->all()));

        return $next($request);
    }

    protected function sanitizeArray(array $payload): array
    {
        return collect($payload)
            ->map(fn (mixed $value): mixed => $this->sanitizeValue($value))
            ->all();
    }

    protected function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }

        if (! is_string($value)) {
            return $value;
        }

        return trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value));
    }
}
