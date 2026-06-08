<?php

declare(strict_types=1);

namespace Tests\Feature\Compliance;

use App\Compliance\DeadlineGenerator;
use App\Compliance\UaeWorkingDayCalendar;
use App\Enums\DeadlineStatus;
use App\Enums\RenewalCycle;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class DeadlineGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private DeadlineGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-06-07 09:00:00'));
        config([
            'compliance.alert_lead_days' => [90, 60, 30, 7, 1],
            'compliance.alert_channels' => ['email'],
        ]);
        $this->generator = $this->app->make(DeadlineGenerator::class);
    }

    private function confirmedDocument(DocumentType $type, ?string $issue, ?string $expiry): Document
    {
        return Document::factory()
            ->forEntity(Entity::factory()->create())
            ->confirmed()
            ->create([
                'document_type_id' => $type->id,
                'issue_date' => $issue,
                'expiry_date' => $expiry,
            ]);
    }

    public function test_generates_deadline_at_expiry_with_lead_and_status(): void
    {
        $type = DocumentType::factory()->create([
            'default_renewal_cycle' => RenewalCycle::Annual,
            'default_lead_days' => 90,
        ]);
        $document = $this->confirmedDocument($type, '2026-06-01', '2027-06-01');

        $deadline = $this->generator->generate($document);

        $this->assertNotNull($deadline);
        $this->assertSame('2027-06-01', $deadline->due_date->toDateString());
        $this->assertSame(90, $deadline->lead_days);
        $this->assertSame(DeadlineStatus::Safe, $deadline->status);
        $this->assertSame($document->tenant_id, $deadline->tenant_id);
    }

    public function test_status_is_due_soon_within_the_lead_window(): void
    {
        $type = DocumentType::factory()->create([
            'default_renewal_cycle' => RenewalCycle::Annual,
            'default_lead_days' => 90,
        ]);
        // ~38 days away → inside the 90-day lead window.
        $deadline = $this->generator->generate($this->confirmedDocument($type, '2025-07-15', '2026-07-15'));

        $this->assertSame(DeadlineStatus::DueSoon, $deadline->status);
    }

    public function test_schedules_the_full_alert_cadence_before_due(): void
    {
        $type = DocumentType::factory()->create(['default_lead_days' => 90]);
        $deadline = $this->generator->generate($this->confirmedDocument($type, '2026-06-01', '2027-06-01'));

        // All five offsets are in the future for a year-out expiry.
        $this->assertSame(5, $deadline->alerts()->count());
        $offsets = $deadline->alerts()->orderBy('scheduled_for')->pluck('scheduled_for')
            ->map(fn ($d) => $d->toDateString())->all();
        $this->assertSame([
            '2027-03-03', // -90
            '2027-04-02', // -60
            '2027-05-02', // -30
            '2027-05-25', // -7
            '2027-05-31', // -1
        ], $offsets);
    }

    public function test_does_not_schedule_alerts_in_the_past(): void
    {
        $type = DocumentType::factory()->create(['default_lead_days' => 90]);
        // ~38 days out: the -90 and -60 day alerts are already in the past.
        $deadline = $this->generator->generate($this->confirmedDocument($type, '2025-07-15', '2026-07-15'));

        $this->assertSame(3, $deadline->alerts()->count()); // -30, -7, -1
    }

    public function test_refuses_to_generate_from_an_unconfirmed_document(): void
    {
        $type = DocumentType::factory()->create();
        $document = Document::factory()->forEntity(Entity::factory()->create())->create([
            'document_type_id' => $type->id,
            'expiry_date' => '2027-01-01',
            'confirmed' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->generator->generate($document);
    }

    public function test_uses_fixed_due_date_for_milestone_types(): void
    {
        $type = DocumentType::factory()->create([
            'default_renewal_cycle' => RenewalCycle::OneTime,
            'fixed_due_date' => '2026-10-30',
        ]);
        $deadline = $this->generator->generate($this->confirmedDocument($type, null, null));

        $this->assertSame('2026-10-30', $deadline->due_date->toDateString());
    }

    public function test_derives_due_from_issue_plus_cycle_when_expiry_unknown(): void
    {
        $type = DocumentType::factory()->create(['default_renewal_cycle' => RenewalCycle::Annual]);
        $deadline = $this->generator->generate($this->confirmedDocument($type, '2026-03-01', null));

        $this->assertSame('2027-03-01', $deadline->due_date->toDateString());
    }

    public function test_uses_business_day_offset_for_the_fta_trigger(): void
    {
        $eventDate = Carbon::parse('2026-06-07')->next(CarbonInterface::MONDAY);
        $type = DocumentType::factory()->create([
            'default_renewal_cycle' => RenewalCycle::Custom,
            'business_day_offset' => 20,
            'alert_offset_days' => [10, 5, 2, 1],
        ]);
        $deadline = $this->generator->generate($this->confirmedDocument($type, $eventDate->toDateString(), null));

        $expected = (new UaeWorkingDayCalendar([]))->addBusinessDays($eventDate, 20)->toDateString();
        $this->assertSame($expected, $deadline->due_date->toDateString());
    }

    public function test_respects_per_type_alert_offset_override(): void
    {
        $type = DocumentType::factory()->create([
            'default_lead_days' => 30,
            'alert_offset_days' => [10, 5, 2, 1],
        ]);
        $deadline = $this->generator->generate($this->confirmedDocument($type, '2026-06-01', '2027-06-01'));

        $this->assertSame(4, $deadline->alerts()->count());
    }

    public function test_regeneration_is_idempotent(): void
    {
        $type = DocumentType::factory()->create(['default_lead_days' => 90]);
        $document = $this->confirmedDocument($type, '2026-06-01', '2027-06-01');

        $first = $this->generator->generate($document);
        $second = $this->generator->generate($document);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $document->deadlines()->count());
        $this->assertSame(5, $second->alerts()->count());
    }
}
