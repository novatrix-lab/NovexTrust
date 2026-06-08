<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PersonRole;
use App\Models\Entity;
use App\Models\Person;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'entity_id' => null,
            'full_name' => fake()->name(),
            'role' => fake()->randomElement(PersonRole::cases()),
            'passport_no' => strtoupper(fake()->bothify('?#######')),
            'emirates_id_no' => fake()->numerify('784-####-#######-#'),
        ];
    }

    /**
     * Attach to an entity, keeping the tenant consistent.
     */
    public function forEntity(Entity $entity): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $entity->tenant_id,
            'entity_id' => $entity->id,
        ]);
    }
}
