<?php

declare(strict_types=1);

namespace Tests\Unit\Compliance;

use App\Compliance\RenewalCalculator;
use App\Enums\RenewalCycle;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RenewalCalculatorTest extends TestCase
{
    private RenewalCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new RenewalCalculator;
    }

    /**
     * @return list<array{RenewalCycle, string}>
     */
    public static function cycleProvider(): array
    {
        return [
            [RenewalCycle::Annual, '2026-03-01'],
            [RenewalCycle::SemiAnnual, '2025-09-01'],
            [RenewalCycle::Quarterly, '2025-06-01'],
            [RenewalCycle::Monthly, '2025-04-01'],
            [RenewalCycle::Biennial, '2027-03-01'],
            [RenewalCycle::Triennial, '2028-03-01'],
        ];
    }

    #[DataProvider('cycleProvider')]
    public function test_next_due_date_for_recurring_cycles(RenewalCycle $cycle, string $expected): void
    {
        $from = CarbonImmutable::parse('2025-03-01');

        $this->assertSame($expected, $this->calc->nextDueDate($from, $cycle)?->toDateString());
    }

    public function test_non_recurring_cycles_have_no_next_due_date(): void
    {
        $from = CarbonImmutable::parse('2025-03-01');

        $this->assertNull($this->calc->nextDueDate($from, RenewalCycle::OneTime));
        $this->assertNull($this->calc->nextDueDate($from, RenewalCycle::Custom));
        $this->assertNull($this->calc->cycleInterval(RenewalCycle::OneTime));
    }
}
