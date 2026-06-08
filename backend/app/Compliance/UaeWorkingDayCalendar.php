<?php

declare(strict_types=1);

namespace App\Compliance;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;

/**
 * UAE working-day calendar. The UAE weekend is Saturday–Sunday and the working
 * week is Monday–Friday (since Jan 2022). Public holidays are data-driven
 * (config/compliance.php) because Islamic-calendar dates shift each year.
 *
 * Powers the FTA "20 business days" tax-record-update trigger (uae-rule-data.md
 * correction 2), where "business days" matters.
 */
final class UaeWorkingDayCalendar
{
    /** @var array<int, string> ISO date strings (Y-m-d) */
    private array $holidays;

    /**
     * @param  array<int, string>|null  $holidays
     */
    public function __construct(?array $holidays = null)
    {
        $this->holidays = $holidays ?? config('compliance.uae_public_holidays', []);
    }

    public function isWeekend(DateTimeInterface $date): bool
    {
        $dow = CarbonImmutable::instance($date)->dayOfWeek;

        return $dow === CarbonInterface::SATURDAY || $dow === CarbonInterface::SUNDAY;
    }

    public function isHoliday(DateTimeInterface $date): bool
    {
        return in_array(CarbonImmutable::instance($date)->toDateString(), $this->holidays, true);
    }

    public function isWorkingDay(DateTimeInterface $date): bool
    {
        return !$this->isWeekend($date) && !$this->isHoliday($date);
    }

    /**
     * Add a number of UAE business days to a date, skipping weekends and
     * public holidays. The start date itself is not counted.
     */
    public function addBusinessDays(DateTimeInterface $from, int $businessDays): CarbonImmutable
    {
        $date = CarbonImmutable::instance($from);
        $remaining = $businessDays;

        while ($remaining > 0) {
            $date = $date->addDay();

            if ($this->isWorkingDay($date)) {
                $remaining--;
            }
        }

        return $date;
    }
}
