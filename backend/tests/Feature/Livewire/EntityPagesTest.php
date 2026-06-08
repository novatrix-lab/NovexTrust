<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Entities\Index;
use App\Livewire\Entities\Show;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EntityPagesTest extends TestCase
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

    public function test_index_lists_only_the_callers_entities(): void
    {
        Entity::factory()->for($this->tenant)->create(['legal_name' => 'Alpha Holdings']);
        Entity::factory()->for(Tenant::factory()->create())->create(['legal_name' => 'Beta Trading']);

        Livewire::test(Index::class)
            ->assertSee('Alpha Holdings')
            ->assertDontSee('Beta Trading');
    }

    public function test_show_renders_a_tenant_entity(): void
    {
        $entity = Entity::factory()->for($this->tenant)->create(['legal_name' => 'Alpha Holdings']);

        Livewire::test(Show::class, ['id' => (string) $entity->id])
            ->assertSee('Alpha Holdings');
    }

    public function test_show_404s_for_another_tenants_entity(): void
    {
        $foreign = Entity::factory()->for(Tenant::factory()->create())->create();

        $this->expectException(ModelNotFoundException::class);
        Livewire::test(Show::class, ['id' => (string) $foreign->id]);
    }
}
