<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Compliance\DeadlineGenerator;
use App\Enums\AlertChannel;
use App\Enums\AlertStatus;
use App\Enums\RenewalCycle;
use App\Enums\UserRole;
use App\Jobs\SendAlert;
use App\Models\Alert;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Whatsapp\WhatsappSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WhatsappAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_alerts_are_generated_when_the_channel_is_enabled(): void
    {
        $this->travelTo(Carbon::parse('2026-06-08'));
        config(['compliance.alert_channels' => ['email', 'whatsapp']]);

        $type = DocumentType::factory()->create([
            'default_renewal_cycle' => RenewalCycle::Annual,
            'default_lead_days' => 90,
        ]);
        $document = Document::factory()
            ->forEntity(Entity::factory()->create())
            ->confirmed()
            ->create(['document_type_id' => $type->id, 'expiry_date' => '2027-06-01']);

        $this->app->make(DeadlineGenerator::class)->generate($document);

        $this->assertGreaterThan(0, Alert::withoutTenancy()->where('channel', AlertChannel::Whatsapp->value)->count());
        $this->assertGreaterThan(0, Alert::withoutTenancy()->where('channel', AlertChannel::Email->value)->count());
    }

    public function test_sends_a_due_whatsapp_alert_through_the_twilio_channel(): void
    {
        config([
            'services.twilio.sid' => 'ACtest',
            'services.twilio.whatsapp_from' => '+14155238886',
        ]);

        $sender = new class implements WhatsappSender
        {
            /** @var list<array{to: string, body: string}> */
            public array $sent = [];

            public function send(string $toPhone, string $body): void
            {
                $this->sent[] = ['to' => $toPhone, 'body' => $body];
            }
        };
        $this->app->instance(WhatsappSender::class, $sender);

        $tenant = Tenant::factory()->create();
        User::factory()->for($tenant)->create(['role' => UserRole::AgencyOwner, 'phone' => '+971500000000']);
        $entity = Entity::factory()->for($tenant)->create();
        $type = DocumentType::factory()->create(['consequence_note' => 'Matters.']);
        $document = Document::factory()->forEntity($entity)->confirmed()->create([
            'document_type_id' => $type->id, 'expiry_date' => '2026-09-01',
        ]);
        $deadline = Deadline::factory()->forDocument($document)->create(['due_date' => '2026-09-01']);
        $alert = Alert::factory()->forDeadline($deadline)->create([
            'channel' => AlertChannel::Whatsapp,
            'status' => AlertStatus::Pending,
            'scheduled_for' => now()->subDay(),
        ]);

        SendAlert::dispatchSync($alert->id);

        $this->assertCount(1, $sender->sent);
        $this->assertSame('+971500000000', $sender->sent[0]['to']);
        $this->assertSame(AlertStatus::Sent, $alert->fresh()->status);
    }
}
