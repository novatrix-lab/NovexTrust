<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Applied to every tenant-owned model. It does two things:
 *
 *  1. Registers {@see TenantScope} so reads are automatically constrained to the
 *     current tenant.
 *  2. Stamps the tenant id on insert from the current context, so application
 *     code never has to set it by hand (and cannot accidentally cross tenants).
 *
 * In Phase 1 the tenant-owned models grow milestone by milestone (entities,
 * persons, documents, deadlines, …); each simply `use`s this trait.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            $context = app(TenantContext::class);
            $column = $model->getTenantColumn();

            if ($context->hasTenant() && empty($model->{$column})) {
                $model->{$column} = $context->id();
            }
        });
    }

    public function getTenantColumn(): string
    {
        return 'tenant_id';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Explicitly bypass tenant scoping for a query. Use deliberately — e.g. the
     * pre-auth credential lookup during login, or cross-tenant admin reads.
     */
    public function scopeWithoutTenancy(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
