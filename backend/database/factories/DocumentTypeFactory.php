<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Enums\DocumentLevel;
use App\Enums\RenewalCycle;
use App\Models\DocumentType;
use App\Models\RulePack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rule_pack_id' => RulePack::factory(),
            'country' => 'AE',
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'category' => fake()->randomElement(DocumentCategory::cases()),
            'level' => fake()->randomElement(DocumentLevel::cases()),
            'jurisdiction' => null,
            'default_renewal_cycle' => RenewalCycle::Annual,
            'default_lead_days' => 30,
            'consequence_note' => fake()->sentence(),
        ];
    }
}
