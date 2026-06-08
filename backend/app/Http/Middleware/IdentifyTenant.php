<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establishes the tenant for the request from the authenticated user. Runs after
 * authentication. A platform admin has no tenant_id, so context stays empty and
 * they operate across tenants (scope inactive).
 */
final class IdentifyTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->tenant_id !== null) {
            $this->context->set((int) $user->tenant_id);
        }

        return $next($request);
    }
}
