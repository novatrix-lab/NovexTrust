<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\ImportEntities;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ImportEntitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_a_csv_creates_tenant_entities(): void
    {
        $tenant = Tenant::factory()->create();
        $this->actingAs(User::factory()->for($tenant)->create());
        app(TenantContext::class)->set($tenant->id);

        $csv = "legal_name,jurisdiction_type\nAlpha Holdings,freezone\nBeta Trading,mainland";
        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        Livewire::test(ImportEntities::class)
            ->set('file', $file)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertSame(2, Entity::query()->count());
    }
}
