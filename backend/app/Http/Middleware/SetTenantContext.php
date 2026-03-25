<?php

namespace App\Http\Middleware;

use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function __construct(
        protected TenantContext $tenantContext,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->setFromUser($request->user());

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }
}
