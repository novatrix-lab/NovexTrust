<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\AlertChannel;
use App\Enums\AlertStatus;
use App\Enums\UserRole;
use App\Jobs\SendAlert;
use App\Mail\AlertMail;
use App\Models\Alert;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AlertMessage;
use App\Notifications\NotificationChannel;
use App\Notifications\NotificationChannelRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendDueAlertsTest extends TestCase
{
    use RefreshDatabase;

    private Deadline $deadline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-06-07 09:00:00'));

        $tenant = Tenant::factory()->create();
        User::factory()->for($tenant)->create(['role' => UserRole::AgencyOwner, 'email' => 'owner@t.test']);
        $entity = Entity::factory()->for($tenant)->create();
        $type = DocumentType::factory()->create(['consequence_note' => 'Matters a lot.']);
        $document = Document::factory()->forEntity($entity)->confirmed()->create([
            'document_type_id' => $type->id, 'expiry_date' => '2026-09-01',
        ]);
        $this->deadline = Deadline::factory()->forDocument($document)->create(['due_date' => '2026-09-01']);
    }

    private function alert(string $scheduledFor): Alert
    {
        return Alert::factory()->forDeadline($this->deadline)->create([
            'channel' => AlertChannel::Email,
            'status' => AlertStatus::Pending,
            'scheduled_for' => $scheduledFor,
        ]);
    }

    public function test_sends_due_alerts_only_and_marks_them_sent(): void
    {
        Mail::fake();
        $due = $this->alert(now()->subDay()->toDateTimeString());
        $future = $this->alert(now()->addDays(10)->toDateTimeString());

        $this->artisan('alerts:send')->expectsOutputToContain('Dispatched 1')->assertSuccessful();

        Mail::assertSent(AlertMail::class, 1);
        $this->assertSame(AlertStatus::Sent, $due->fresh()->status);
        $this->assertNotNull($due->fresh()->sent_at);
        $this->assertSame(AlertStatus::Pending, $future->fresh()->status);
    }

    public function test_is_idempotent_and_does_not_resend(): void
    {
        Mail::fake();
        $this->alert(now()->subDay()->toDateTimeString());

        $this->artisan('alerts:send')->assertSuccessful();
        $this->artisan('alerts:send')->expectsOutputToContain('Dispatched 0')->assertSuccessful();

        Mail::assertSent(AlertMail::class, 1);
    }

    public function test_a_channel_failure_marks_the_alert_failed(): void
    {
        $registry = new NotificationChannelRegistry;
        $registry->register(new class implements NotificationChannel
        {
            public function channel(): AlertChannel
            {
                return AlertChannel::Email;
            }

            public function send(AlertMessage $message): void
            {
                throw new \RuntimeException('delivery boom');
            }
        });
        $this->app->instance(NotificationChannelRegistry::class, $registry);

        $alert = $this->alert(now()->subDay()->toDateTimeString());

        SendAlert::dispatchSync($alert->id);

        $this->assertSame(AlertStatus::Failed, $alert->fresh()->status);
    }
}
