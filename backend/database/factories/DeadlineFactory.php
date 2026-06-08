<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DeadlineStatus;
use App\Models\Deadline;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deadline>
 */
class DeadlineFactory extends Factory
{
    protected $model = Deadline::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'due_date' => now()->addMonths(3)->toDateString(),
            'lead_days' => 30,
            'status' => DeadlineStatus::Safe,
            'responsible_user_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Deadline $deadline): void {
            if ($deadline->document_id === null) {
                $deadline->document()->associate(Document::factory()->create());
            }

            if ($deadline->tenant_id === null) {
                $deadline->tenant_id = $deadline->document?->tenant_id;
            }
        });
    }

    public function forDocument(Document $document): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $document->tenant_id,
            'document_id' => $document->id,
        ]);
    }
}
