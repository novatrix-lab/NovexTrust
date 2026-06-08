<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RulePack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RulePack>
 */
class RulePackFactory extends Factory
{
    protected $model = RulePack::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country' => 'AE',
            'version' => fake()->unique()->numerify('2026.#.#'),
            'active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['active' => true]);
    }
}
