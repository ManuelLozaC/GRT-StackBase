<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\OpenApi\OpenApiDocumentBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function __invoke(OpenApiDocumentBuilder $builder): JsonResponse
    {
        return response()->json($builder->build());
    }
}
