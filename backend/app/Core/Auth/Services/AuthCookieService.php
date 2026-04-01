<?php

namespace App\Core\Auth\Services;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class AuthCookieService
{
    public function make(string $plainTextToken): Cookie
    {
        return cookie(
            name: (string) config('security.auth_cookie.name', 'stackbase_access_token'),
            value: $plainTextToken,
            minutes: (int) config('security.auth_cookie.minutes', 480),
            path: (string) config('security.auth_cookie.path', '/'),
            domain: config('security.auth_cookie.domain'),
            secure: (bool) config('security.auth_cookie.secure', false),
            httpOnly: true,
            raw: false,
            sameSite: (string) config('security.auth_cookie.same_site', 'lax'),
        );
    }

    public function makeCsrf(string $plainTextToken): Cookie
    {
        return cookie(
            name: (string) config('security.auth_cookie.csrf_cookie_name', 'stackbase_xsrf_token'),
            value: $this->csrfTokenFor($plainTextToken),
            minutes: (int) config('security.auth_cookie.minutes', 480),
            path: (string) config('security.auth_cookie.path', '/'),
            domain: config('security.auth_cookie.domain'),
            secure: (bool) config('security.auth_cookie.secure', false),
            httpOnly: false,
            raw: false,
            sameSite: (string) config('security.auth_cookie.same_site', 'lax'),
        );
    }

    public function expire(): Cookie
    {
        return cookie()->forget(
            name: (string) config('security.auth_cookie.name', 'stackbase_access_token'),
            path: (string) config('security.auth_cookie.path', '/'),
            domain: config('security.auth_cookie.domain'),
        );
    }

    public function expireCsrf(): Cookie
    {
        return cookie()->forget(
            name: (string) config('security.auth_cookie.csrf_cookie_name', 'stackbase_xsrf_token'),
            path: (string) config('security.auth_cookie.path', '/'),
            domain: config('security.auth_cookie.domain'),
        );
    }

    public function resolveFromRequestCookie(?string $tokenFromCookie): ?string
    {
        $token = trim((string) $tokenFromCookie);

        return $token !== '' ? $token : null;
    }

    public function csrfTokenFor(string $plainTextToken): string
    {
        return hash_hmac(
            'sha256',
            $plainTextToken,
            (string) config('app.key', Str::random(32)),
        );
    }
}
