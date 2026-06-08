<?php

declare(strict_types=1);

namespace App\Compliance;

use App\Enums\DeadlineStatus;
use App\Models\Deadline;
use App\Models\Document;

/**
 * The daily compliance recompute (SPEC.md §8, CLAUDE.md M7). Runs system-wide
 * (no tenant context), so all queries bypass the tenant scope.
 *
 *  1. Backfill: confirmed documents that have no deadline get one (the M6 confirm
 *     step already generates them; this is the safety net). Deadlines are only
 *     ever created from CONFIRMED documents.
 *  2. Recompute: every open deadline's traffic-light status is refreshed.
 *     `Done` is terminal and is never overwritten.
 */
final class DeadlineRecomputer
{
    public function __construct(
        private readonly DeadlineGenerator $generator,
        private readonly DeadlineStatusCalculator $status,
    ) {}

    /**
     * @return array{deadlines_created: int, statuses_updated: int}
     */
    public function run(): array
    {
        return [
            'deadlines_created' => $this->backfillMissingDeadlines(),
            'statuses_updated' => $this->recomputeStatuses(),
        ];
    }

    private function backfillMissingDeadlines(): int
    {
        $created = 0;

        Document::withoutTenancy()
            ->where('confirmed', true)
            ->whereDoesntHave('deadlines')
            ->chunkById(200, function ($documents) use (&$created): void {
                foreach ($documents as $document) {
                    if ($this->generator->generate($document) !== null) {
                        $created++;
                    }
                }
            });

        return $created;
    }

    private function recomputeStatuses(): int
    {
        $updated = 0;

        Deadline::withoutTenancy()
            ->where('status', '!=', DeadlineStatus::Done->value)
            ->chunkById(500, function ($deadlines) use (&$updated): void {
                foreach ($deadlines as $deadline) {
                    $status = $this->status->calculate($deadline->due_date, $deadline->lead_days);

                    if ($deadline->status !== $status) {
                        $deadline->status = $status;
                        $deadline->save();
                        $updated++;
                    }
                }
            });

        return $updated;
    }
}
