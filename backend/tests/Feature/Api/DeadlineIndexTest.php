<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\DeadlineStatus;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeadlineIndexTest extends TestCase
{
    use RefreshDatabase;

    private function deadlineFor(Tenant $tenant, DeadlineStatus $status = DeadlineStatus::Safe): Deadline
    {
        $document = Document::factory()->forEntity(Entity::factory()->for($tenant)->create())->create();

        return Deadline::factory()->forDocument($document)->create(['status' => $status]);
    }

    public function test_index_returns_only_the_callers_tenant_deadlines(): void
    {
        $tenantA = Tenant::factory()->create();
        $this->deadlineFor($tenantA);
        $this->deadlineFor($tenantA);
        $this->deadlineFor(Tenant::factory()->create()); // another tenant

        Sanctum::actingAs(User::factory()->for($tenantA)->create());

        $this->getJson('/api/deadlines')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_can_filter_by_status(): void
    {
        $tenant = Tenant::factory()->create();
        $this->deadlineFor($tenant, DeadlineStatus::Overdue);
        $this->deadlineFor($tenant, DeadlineStatus::Safe);

        Sanctum::actingAs(User::factory()->for($tenant)->create());

        $this->getJson('/api/deadlines?status=overdue')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', DeadlineStatus::Overdue->value);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/deadlines')->assertUnauthorized();
    }
}
