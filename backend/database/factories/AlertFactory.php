<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AlertChannel;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\Deadline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel' => AlertChannel::Email,
            'scheduled_for' => now()->addMonths(2),
            'sent_at' => null,
            'status' => AlertStatus::Pending,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Alert $alert): void {
            if ($alert->deadline_id === null) {
                $alert->deadline()->associate(Deadline::factory()->create());
            }

            if ($alert->tenant_id === null) {
                $alert->tenant_id = $alert->deadline?->tenant_id;
            }
        });
    }

    public function forDeadline(Deadline $deadline): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $deadline->tenant_id,
            'deadline_id' => $deadline->id,
        ]);
    }
}
