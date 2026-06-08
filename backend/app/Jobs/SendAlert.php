<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Notifications\AlertMessageBuilder;
use App\Notifications\NotificationChannelRegistry;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Sends a single alert through its channel and records the outcome. Idempotent:
 * ShouldBeUnique prevents duplicate queued jobs per alert, and the status guard
 * skips an alert that has already been handled.
 */
class SendAlert implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $alertId) {}

    public function uniqueId(): string
    {
        return (string) $this->alertId;
    }

    public function handle(AlertMessageBuilder $builder, NotificationChannelRegistry $registry): void
    {
        $alert = Alert::withoutTenancy()->find($this->alertId);

        if ($alert === null || $alert->status !== AlertStatus::Pending) {
            return; // already sent/failed/cancelled, or gone
        }

        try {
            $registry->for($alert->channel)->send($builder->build($alert));
            $alert->update(['status' => AlertStatus::Sent, 'sent_at' => now()]);
        } catch (Throwable $e) {
            $alert->update(['status' => AlertStatus::Failed]);
            report($e);
        }
    }
}
