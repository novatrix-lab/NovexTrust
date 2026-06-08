<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AlertStatus;
use App\Jobs\SendAlert;
use App\Models\Alert;
use Illuminate\Console\Command;

/**
 * Dispatches send jobs for every pending alert whose scheduled time has arrived
 * (SPEC.md §8: queue all sends). Scheduled hourly; the cron-driven scheduler
 * runs it in production (SPEC.md §11).
 */
class SendDueAlerts extends Command
{
    protected $signature = 'alerts:send';

    protected $description = 'Queue delivery of all due, pending alerts';

    public function handle(): int
    {
        $dispatched = 0;

        Alert::withoutTenancy()
            ->where('status', AlertStatus::Pending->value)
            ->where('scheduled_for', '<=', now())
            ->orderBy('id')
            ->chunkById(500, function ($alerts) use (&$dispatched): void {
                foreach ($alerts as $alert) {
                    SendAlert::dispatch($alert->id);
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} alert(s).");

        return self::SUCCESS;
    }
}
