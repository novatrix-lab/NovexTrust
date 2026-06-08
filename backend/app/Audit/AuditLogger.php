<?php

declare(strict_types=1);

namespace App\Audit;

use App\Models\AuditLog;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes audit-trail entries (SPEC.md §8/§10). Resolves the acting user, tenant
 * and IP from the current context. Auditing must never break the operation being
 * audited, so failures are swallowed.
 */
final class AuditLogger
{
    public function __construct(private readonly TenantContext $context) {}

    public function log(string $action, ?Model $target = null, ?User $actor = null): ?AuditLog
    {
        $actor ??= Auth::user();

        try {
            return AuditLog::create([
                'tenant_id' => $actor?->tenant_id ?? $this->context->id(),
                'user_id' => $actor?->getKey(),
                'action' => $action,
                'target_type' => $target?->getMorphClass(),
                'target_id' => $target?->getKey(),
                'ip' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
