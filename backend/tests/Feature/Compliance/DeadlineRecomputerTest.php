<?php

declare(strict_types=1);

namespace Tests\Feature\Compliance;

use App\Compliance\DeadlineRecomputer;
use App\Enums\DeadlineStatus;
use App\Enums\RenewalCycle;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeadlineRecomputerTest extends TestCase
{
    use RefreshDatabase;

    private function recomputer(): DeadlineRecomputer
    {
        return $this->app->make(DeadlineRecomputer::class);
    }

    private function annualType(int $lead = 90): DocumentType
    {
        return DocumentType::factory()->create([
            'default_renewal_cycle' => RenewalCycle::Annual,
            'default_lead_days' => $lead,
        ]);
    }

    public function test_backfills_deadline_for_confirmed_document(): void
    {
        $this->travelTo(Carbon::parse('2026-06-07'));
        $document = Document::factory()
            ->forEntity(Entity::factory()->create())
            ->confirmed()
            ->create(['document_type_id' => $this->annualType()->id, 'expiry_date' => '2027-06-01']);

        $summary = $this->recomputer()->run();

        $this->assertSame(1, $summary['deadlines_created']);
        $this->assertSame(1, $document->deadlines()->count());
        $this->assertSame('2027-06-01', $document->deadlines()->first()->due_date->toDateString());
    }

    public function test_does_not_create_deadlines_for_unconfirmed_documents(): void
    {
        Document::factory()
            ->forEntity(Entity::factory()->create())
            ->create(['document_type_id' => $this->annualType()->id, 'expiry_date' => '2027-06-01', 'confirmed' => false]);

        $summary = $this->recomputer()->run();

        $this->assertSame(0, $summary['deadlines_created']);
        $this->assertDatabaseCount('deadlines', 0);
    }

    public function test_recomputes_status_as_time_advances(): void
    {
        // A deadline due 2026-09-01, lead 90 days.
        $deadline = Deadline::factory()->create([
            'due_date' => '2026-09-01',
            'lead_days' => 90,
            'status' => DeadlineStatus::Safe,
        ]);

        // 120 days out → still Safe.
        $this->travelTo(Carbon::parse('2026-05-01'));
        $this->recomputer()->run();
        $this->assertSame(DeadlineStatus::Safe, $deadline->fresh()->status);

        // ~86 days out → DueSoon.
        $this->travelTo(Carbon::parse('2026-06-07'));
        $this->recomputer()->run();
        $this->assertSame(DeadlineStatus::DueSoon, $deadline->fresh()->status);

        // Past due → Overdue.
        $this->travelTo(Carbon::parse('2026-09-02'));
        $this->recomputer()->run();
        $this->assertSame(DeadlineStatus::Overdue, $deadline->fresh()->status);
    }

    public function test_never_overwrites_a_done_deadline(): void
    {
        $deadline = Deadline::factory()->create([
            'due_date' => '2020-01-01', // long overdue by date
            'lead_days' => 30,
            'status' => DeadlineStatus::Done,
        ]);

        $this->recomputer()->run();

        $this->assertSame(DeadlineStatus::Done, $deadline->fresh()->status);
    }

    public function test_is_idempotent(): void
    {
        $this->travelTo(Carbon::parse('2026-06-07'));
        $document = Document::factory()
            ->forEntity(Entity::factory()->create())
            ->confirmed()
            ->create(['document_type_id' => $this->annualType()->id, 'expiry_date' => '2027-06-01']);

        $this->recomputer()->run();
        $second = $this->recomputer()->run();

        $this->assertSame(0, $second['deadlines_created']);
        $this->assertSame(1, $document->deadlines()->count());
    }
}
