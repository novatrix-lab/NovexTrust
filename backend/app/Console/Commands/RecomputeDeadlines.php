<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Compliance\DeadlineRecomputer;
use Illuminate\Console\Command;

/**
 * Daily compliance recompute. Scheduled in routes/console.php; in production the
 * cron entry `* * * * * php artisan schedule:run` drives it (SPEC.md §11).
 */
class RecomputeDeadlines extends Command
{
    protected $signature = 'deadlines:recompute';

    protected $description = 'Backfill deadlines for confirmed documents and recompute deadline statuses';

    public function handle(DeadlineRecomputer $recomputer): int
    {
        $summary = $recomputer->run();

        $this->info(sprintf(
            'Deadlines created: %d; statuses updated: %d.',
            $summary['deadlines_created'],
            $summary['statuses_updated'],
        ));

        return self::SUCCESS;
    }
}
