<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\DeadlineStatus;
use App\Livewire\Dashboard;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->actingAs(User::factory()->for($this->tenant)->create());
        app(TenantContext::class)->set($this->tenant->id);
    }

    private function deadlineFor(Tenant $tenant, string $entityName): Deadline
    {
        $entity = Entity::factory()->for($tenant)->create(['legal_name' => $entityName]);
        $document = Document::factory()->forEntity($entity)->create();

        return Deadline::factory()->forDocument($document)->create();
    }

    public function test_shows_only_the_callers_tenant_deadlines(): void
    {
        $this->deadlineFor($this->tenant, 'Alpha Holdings');
        $this->deadlineFor(Tenant::factory()->create(), 'Beta Trading');

        Livewire::test(Dashboard::class)
            ->assertSee('Alpha Holdings')
            ->assertDontSee('Beta Trading');
    }

    public function test_can_mark_a_deadline_done(): void
    {
        $deadline = $this->deadlineFor($this->tenant, 'Alpha Holdings');

        Livewire::test(Dashboard::class)->call('markDone', $deadline->id);

        $this->assertSame(DeadlineStatus::Done, $deadline->fresh()->status);
    }

    public function test_can_assign_a_responsible_user(): void
    {
        $deadline = $this->deadlineFor($this->tenant, 'Alpha Holdings');
        $staff = User::factory()->for($this->tenant)->create();

        Livewire::test(Dashboard::class)->call('assign', $deadline->id, $staff->id);

        $this->assertSame($staff->id, $deadline->fresh()->responsible_user_id);
    }

    public function test_cannot_mark_done_another_tenants_deadline(): void
    {
        $foreign = $this->deadlineFor(Tenant::factory()->create(), 'Beta Trading');

        $this->expectException(ModelNotFoundException::class);
        Livewire::test(Dashboard::class)->call('markDone', $foreign->id);
    }
}
