<?php

declare(strict_types=1);

namespace App\Compliance;

use App\Enums\RenewalCycle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use DateTimeInterface;

/**
 * Pure renewal-cycle maths. Kept free of persistence so it can be exhaustively
 * unit-tested — this is the heart of the moat (CLAUDE.md quality bar).
 */
final class RenewalCalculator
{
    /**
     * The concrete interval a renewal cycle represents, or null for cycles that
     * do not recur on a fixed period (one-time milestones, custom triggers).
     */
    public function cycleInterval(RenewalCycle $cycle): ?CarbonInterval
    {
        return match ($cycle) {
            RenewalCycle::Annual => CarbonInterval::year(),
            RenewalCycle::SemiAnnual => CarbonInterval::months(6),
            RenewalCycle::Quarterly => CarbonInterval::months(3),
            RenewalCycle::Monthly => CarbonInterval::month(),
            RenewalCycle::Biennial => CarbonInterval::years(2),
            RenewalCycle::Triennial => CarbonInterval::years(3),
            RenewalCycle::OneTime, RenewalCycle::Custom => null,
        };
    }

    /**
     * The next due date for a cycle starting from a reference date (e.g. expiry
     * = issue + cycle). Null when the cycle has no fixed interval.
     */
    public function nextDueDate(DateTimeInterface $from, RenewalCycle $cycle): ?CarbonImmutable
    {
        $interval = $this->cycleInterval($cycle);

        if ($interval === null) {
            return null;
        }

        return CarbonImmutable::instance($from)->add($interval);
    }
}
