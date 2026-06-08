<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Http\Middleware\IdentifyTenant;
use App\Models\Scopes\TenantScope;
use App\Providers\AppServiceProvider;
use Closure;

/**
 * Holds the current tenant for the lifetime of a request (or console action).
 *
 * Registered as a singleton (see {@see AppServiceProvider}). The
 * {@see IdentifyTenant} middleware populates it from the
 * authenticated user; {@see TenantScope} reads it to constrain
 * every tenant-owned query. When no tenant is set (e.g. platform admin, console,
 * pre-auth), tenant scoping is inactive — so guard those paths deliberately.
 */
final class TenantContext
{
    private ?int $tenantId = null;

    public function set(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function id(): ?int
    {
        return $this->tenantId;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    public function forget(): void
    {
        $this->tenantId = null;
    }

    /**
     * Run a callback with tenant scoping disabled, restoring the prior tenant
     * afterwards. Use sparingly and intentionally (e.g. cross-tenant admin work).
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function runWithout(Closure $callback): mixed
    {
        $previous = $this->tenantId;
        $this->tenantId = null;

        try {
            return $callback();
        } finally {
            $this->tenantId = $previous;
        }
    }
}
