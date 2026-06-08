<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consent;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consent>
 */
class ConsentFactory extends Factory
{
    protected $model = Consent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'purpose' => fake()->randomElement(['document_processing', 'cross_border_transfer', 'marketing']),
            'granted_at' => now(),
            'transfer_basis' => 'SCCs',
        ];
    }
}
