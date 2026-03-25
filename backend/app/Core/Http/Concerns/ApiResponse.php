<?php

namespace App\Core\Http\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
    ): JsonResponse {
        return response()->json([
            'estado' => 'ok',
            'datos' => $data,
            'mensaje' => $message,
            'meta' => $this->metaWithRequestContext($meta),
            'errores' => [],
        ], $status);
    }

    protected function errorResponse(
        string $message,
        array $errors = [],
        array $meta = [],
        int $status = 422,
    ): JsonResponse {
        return response()->json([
            'estado' => 'error',
            'datos' => null,
            'mensaje' => $message,
            'meta' => $this->metaWithRequestContext($meta),
            'errores' => $errors,
        ], $status);
    }

    protected function metaWithRequestContext(array $meta = []): array
    {
        $requestId = request()?->attributes->get('request_id');

        if ($requestId !== null) {
            $meta['request_id'] = $requestId;
        }

        return $meta;
    }
}
