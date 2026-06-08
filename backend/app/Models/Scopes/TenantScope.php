<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Concerns\BelongsToTenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that constrains every query on a tenant-owned model to the
 * current tenant. This is the enforcement point for the strict per-tenant
 * isolation security property (SPEC.md §10). When no tenant is in context the
 * scope is a no-op (see {@see TenantContext}).
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->hasTenant()) {
            /** @var BelongsToTenant $model */
            $builder->where(
                $model->qualifyColumn($model->getTenantColumn()),
                $context->id(),
            );
        }
    }
}
