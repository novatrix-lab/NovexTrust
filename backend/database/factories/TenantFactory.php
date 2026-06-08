<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(TenantType::cases()),
            'country' => 'AE',
            'status' => 'active',
        ];
    }

    public function agency(): static
    {
        return $this->state(fn (array $attributes) => ['type' => TenantType::Agency]);
    }

    public function sme(): static
    {
        return $this->state(fn (array $attributes) => ['type' => TenantType::Sme]);
    }
}
