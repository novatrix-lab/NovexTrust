<?php

declare(strict_types=1);

namespace Tests\Unit\Compliance;

use App\Compliance\UaeWorkingDayCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use PHPUnit\Framework\TestCase;

class UaeWorkingDayCalendarTest extends TestCase
{
    public function test_saturday_and_sunday_are_the_weekend(): void
    {
        $cal = new UaeWorkingDayCalendar([]);
        $saturday = CarbonImmutable::parse('2026-06-07')->next(CarbonInterface::SATURDAY);

        $this->assertTrue($cal->isWeekend($saturday));
        $this->assertTrue($cal->isWeekend($saturday->addDay()));        // Sunday
        $this->assertFalse($cal->isWeekend($saturday->addDays(2)));     // Monday
        $this->assertTrue($cal->isWorkingDay($saturday->addDays(2)));   // Monday
    }

    public function test_configured_public_holiday_is_not_a_working_day(): void
    {
        $monday = CarbonImmutable::parse('2026-06-07')->next(CarbonInterface::MONDAY);
        $cal = new UaeWorkingDayCalendar([$monday->toDateString()]);

        $this->assertTrue($cal->isHoliday($monday));
        $this->assertFalse($cal->isWorkingDay($monday));
    }

    public function test_add_business_days_skips_the_weekend(): void
    {
        $cal = new UaeWorkingDayCalendar([]);
        $monday = CarbonImmutable::parse('2026-06-07')->next(CarbonInterface::MONDAY);

        // 5 business days from Monday lands on the next Monday (spans a weekend).
        $result = $cal->addBusinessDays($monday, 5);

        $this->assertSame($monday->addDays(7)->toDateString(), $result->toDateString());
        $this->assertTrue($result->isMonday());
    }

    public function test_add_business_days_skips_a_holiday(): void
    {
        $monday = CarbonImmutable::parse('2026-06-07')->next(CarbonInterface::MONDAY);
        $wednesday = $monday->addDays(2);
        $cal = new UaeWorkingDayCalendar([$wednesday->toDateString()]);

        // Tue(1), Wed=holiday, Thu(2), Fri(3) → Friday (Monday + 4 days).
        $result = $cal->addBusinessDays($monday, 3);

        $this->assertSame($monday->addDays(4)->toDateString(), $result->toDateString());
    }

    public function test_twenty_business_days_is_four_clear_weeks(): void
    {
        $cal = new UaeWorkingDayCalendar([]);
        $monday = CarbonImmutable::parse('2026-06-07')->next(CarbonInterface::MONDAY);

        // The FTA 20-business-day trigger, no holidays: exactly 28 calendar days.
        $result = $cal->addBusinessDays($monday, 20);

        $this->assertSame($monday->addDays(28)->toDateString(), $result->toDateString());
    }
}
