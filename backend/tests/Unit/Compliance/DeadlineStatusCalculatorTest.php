<?php

declare(strict_types=1);

namespace Tests\Unit\Compliance;

use App\Compliance\DeadlineStatusCalculator;
use App\Enums\DeadlineStatus;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class DeadlineStatusCalculatorTest extends TestCase
{
    private DeadlineStatusCalculator $calc;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new DeadlineStatusCalculator;
        $this->today = CarbonImmutable::parse('2026-06-07');
    }

    public function test_due_in_the_past_is_overdue(): void
    {
        $this->assertSame(
            DeadlineStatus::Overdue,
            $this->calc->calculate(CarbonImmutable::parse('2026-06-06'), 30, $this->today),
        );
    }

    public function test_due_today_is_due_soon(): void
    {
        $this->assertSame(
            DeadlineStatus::DueSoon,
            $this->calc->calculate($this->today, 30, $this->today),
        );
    }

    public function test_due_within_lead_window_is_due_soon(): void
    {
        $this->assertSame(
            DeadlineStatus::DueSoon,
            $this->calc->calculate($this->today->addDays(30), 30, $this->today),
        );
    }

    public function test_due_just_beyond_lead_window_is_safe(): void
    {
        $this->assertSame(
            DeadlineStatus::Safe,
            $this->calc->calculate($this->today->addDays(31), 30, $this->today),
        );
    }
}
