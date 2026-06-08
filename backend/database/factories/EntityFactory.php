<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JurisdictionType;
use App\Models\Entity;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entity>
 */
class EntityFactory extends Factory
{
    protected $model = Entity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'legal_name' => fake()->company().' LLC',
            'trade_name' => fake()->company(),
            'jurisdiction_type' => fake()->randomElement(JurisdictionType::cases()),
            'authority' => fake()->randomElement(['DED', 'DMCC', 'JAFZA', 'DAFZA', 'ADGM']),
            'license_number' => 'CN-'.fake()->numerify('#######'),
            'country' => 'AE',
            'notes' => null,
        ];
    }
}
