<?php

namespace App\Http\Middleware;

use App\Core\Auth\Services\AuthCookieService;
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
        protected AuthCookieService $authCookies,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $plainTextBearerToken = $request->bearerToken();
        $plainTextCookieToken = $this->authCookies->resolveFromRequestCookie(
            $request->cookie((string) config('security.auth_cookie.name', 'stackbase_access_token'))
        );
        $plainTextToken = $plainTextBearerToken ?: $plainTextCookieToken;
        $authChannel = $plainTextBearerToken ? 'bearer' : ($plainTextCookieToken ? 'cookie' : null);

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return $this->unauthorizedResponse();
        }

        if ($authChannel === 'cookie' && $this->requiresCsrfValidation($request)) {
            $headerName = (string) config('security.auth_cookie.csrf_header_name', 'X-StackBase-CSRF');
            $providedCsrfToken = trim((string) $request->headers->get($headerName));
            $expectedCsrfToken = $this->authCookies->csrfTokenFor($plainTextToken);

            if ($providedCsrfToken === '' || ! hash_equals($expectedCsrfToken, $providedCsrfToken)) {
                return $this->csrfMismatchResponse();
            }
        }

        $token = $this->tokens->findValidToken($plainTextToken);

        if ($token === null || $token->user === null) {
            return $this->unauthorizedResponse();
        }

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('current_access_token', $token);
        $request->attributes->set('auth_channel', $authChannel);

        return $next($request);
    }

    protected function requiresCsrfValidation(Request $request): bool
    {
        if (! (bool) config('security.channels.web_cookie.requires_csrf', true)) {
            return false;
        }

        return in_array(
            $request->getMethod(),
            config('security.channels.web_cookie.csrf_methods', ['POST', 'PUT', 'PATCH', 'DELETE']),
            true,
        );
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

    protected function csrfMismatchResponse(): JsonResponse
    {
        return response()->json([
            'estado' => 'error',
            'datos' => null,
            'mensaje' => 'La validacion CSRF del canal web fallo.',
            'meta' => [
                'error_code' => 'csrf_mismatch',
            ],
            'errores' => [],
        ], 419);
    }
}
