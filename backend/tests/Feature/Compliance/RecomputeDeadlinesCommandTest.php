<?php

declare(strict_types=1);

namespace Tests\Feature\Compliance;

use App\Enums\RenewalCycle;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecomputeDeadlinesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_and_backfills_deadlines(): void
    {
        $this->travelTo(Carbon::parse('2026-06-07'));
        $type = DocumentType::factory()->create([
            'default_renewal_cycle' => RenewalCycle::Annual,
            'default_lead_days' => 90,
        ]);
        Document::factory()
            ->forEntity(Entity::factory()->create())
            ->confirmed()
            ->create(['document_type_id' => $type->id, 'expiry_date' => '2027-06-01']);

        $this->artisan('deadlines:recompute')
            ->expectsOutputToContain('Deadlines created: 1')
            ->assertSuccessful();

        $this->assertDatabaseCount('deadlines', 1);
    }
}
