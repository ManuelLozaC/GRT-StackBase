<?php

namespace App\Http\Middleware;

use App\Core\Auth\Services\AccessTokenService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function __construct(
        protected AccessTokenService $tokens,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return $this->unauthorizedResponse();
        }

        $token = $this->tokens->findValidToken($plainTextToken);

        if ($token === null || $token->user === null) {
            return $this->unauthorizedResponse();
        }

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('current_access_token', $token);

        return $next($request);
    }

    protected function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'estado' => 'error',
            'datos' => null,
            'mensaje' => 'No autenticado',
            'meta' => [],
            'errores' => [],
        ], 401);
    }
}
