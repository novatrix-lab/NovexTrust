<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type_id' => DocumentType::factory(),
            'issue_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addMonths(3)->toDateString(),
            'status' => DocumentStatus::Pending,
            'file_ref' => null,
            'extracted' => false,
            'confirmed' => false,
            'confirmed_by' => null,
            'confirmed_at' => null,
        ];
    }

    /**
     * Ensure every document has a consistent owner: if no entity/person was
     * provided, attach a fresh entity; always derive tenant_id from the parent.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Document $document): void {
            if ($document->entity_id === null && $document->person_id === null) {
                $document->entity()->associate(Entity::factory()->create());
            }

            if ($document->tenant_id === null) {
                $parent = $document->entity ?? $document->person;
                $document->tenant_id = $parent?->tenant_id;
            }
        });
    }

    public function forEntity(Entity $entity): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $entity->tenant_id,
            'entity_id' => $entity->id,
            'person_id' => null,
        ]);
    }

    public function forPerson(Person $person): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $person->tenant_id,
            'person_id' => $person->id,
            'entity_id' => null,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Active,
            'extracted' => true,
            'confirmed' => true,
            'confirmed_at' => now(),
        ]);
    }
}
