<?php

declare(strict_types=1);

namespace App\Compliance;

use App\Enums\DeadlineStatus;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Computes a deadline's traffic-light status from its due date and lead time.
 * Used both at generation (M4) and by the daily scheduler recompute (M7).
 *
 * `Done` is a manual, human-set terminal state and is never produced here.
 */
final class DeadlineStatusCalculator
{
    public function calculate(DateTimeInterface $dueDate, int $leadDays, ?DateTimeInterface $asOf = null): DeadlineStatus
    {
        $today = ($asOf !== null ? CarbonImmutable::instance($asOf) : CarbonImmutable::now())->startOfDay();
        $due = CarbonImmutable::instance($dueDate)->startOfDay();

        if ($due->lessThan($today)) {
            return DeadlineStatus::Overdue;
        }

        if ($due->lessThanOrEqualTo($today->addDays($leadDays))) {
            return DeadlineStatus::DueSoon;
        }

        return DeadlineStatus::Safe;
    }
}
