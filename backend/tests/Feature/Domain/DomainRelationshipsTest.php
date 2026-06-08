<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Models\Alert;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\Person;
use App\Models\RulePack;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_domain_graph_wires_up(): void
    {
        $tenant = Tenant::factory()->create();
        $entity = Entity::factory()->for($tenant)->create();
        $person = Person::factory()->forEntity($entity)->create();

        $pack = RulePack::factory()->create();
        $type = DocumentType::factory()->for($pack)->create();

        $document = Document::factory()->forEntity($entity)->for($type, 'documentType')->create();
        $deadline = Deadline::factory()->forDocument($document)->create();
        $alert = Alert::factory()->forDeadline($deadline)->create();

        // belongsTo
        $this->assertTrue($entity->tenant->is($tenant));
        $this->assertTrue($person->entity->is($entity));
        $this->assertTrue($document->entity->is($entity));
        $this->assertTrue($document->documentType->is($type));
        $this->assertTrue($deadline->document->is($document));
        $this->assertTrue($alert->deadline->is($deadline));
        $this->assertTrue($type->rulePack->is($pack));

        // hasMany
        $this->assertTrue($entity->persons->contains($person));
        $this->assertTrue($entity->documents->contains($document));
        $this->assertTrue($document->deadlines->contains($deadline));
        $this->assertTrue($deadline->alerts->contains($alert));
        $this->assertTrue($pack->documentTypes->contains($type));

        // tenant aggregates
        $this->assertSame(1, $tenant->entities()->count());
        $this->assertSame(1, $tenant->persons()->count());
        $this->assertSame(1, $tenant->documents()->count());
        $this->assertSame(1, $tenant->deadlines()->count());
        $this->assertSame(1, $tenant->alerts()->count());
    }

    public function test_document_can_attach_to_a_person(): void
    {
        $person = Person::factory()->create();
        $document = Document::factory()->forPerson($person)->create();

        $this->assertNull($document->entity_id);
        $this->assertTrue($document->person->is($person));
        $this->assertSame($person->tenant_id, $document->tenant_id);
    }
}
