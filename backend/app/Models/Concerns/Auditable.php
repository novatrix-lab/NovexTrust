<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Auto-records create/update/delete of a model to the audit trail (SPEC.md §8).
 * Applied to user-managed domain records. High-churn, system-generated models
 * (deadlines, alerts) are audited via explicit events instead, to keep the trail
 * meaningful and avoid recompute noise.
 *
 * Note: model events are suppressed during seeding (WithoutModelEvents), so the
 * seeder does not generate audit rows.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => self::recordAudit('created', $model));
        static::updated(fn (Model $model) => self::recordAudit('updated', $model));
        static::deleted(fn (Model $model) => self::recordAudit('deleted', $model));
    }

    protected static function recordAudit(string $event, Model $model): void
    {
        app(AuditLogger::class)->log(strtolower(class_basename($model)).'.'.$event, $model);
    }
}
