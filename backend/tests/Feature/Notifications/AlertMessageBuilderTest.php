<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\AlertChannel;
use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AlertMessageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AlertMessageBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function makeAlert(Tenant $tenant, ?int $responsibleUserId = null): Alert
    {
        $entity = Entity::factory()->for($tenant)->create(['legal_name' => 'Falcon LLC']);
        $type = DocumentType::factory()->create([
            'name' => 'Trade Licence',
            'consequence_note' => 'The company record may be frozen.',
        ]);
        $document = Document::factory()->forEntity($entity)->confirmed()->create([
            'document_type_id' => $type->id,
            'expiry_date' => '2026-08-01',
        ]);
        $deadline = Deadline::factory()->forDocument($document)->create([
            'due_date' => '2026-08-01',
            'responsible_user_id' => $responsibleUserId,
        ]);

        return Alert::factory()->forDeadline($deadline)->create(['channel' => AlertChannel::Email]);
    }

    public function test_builds_localized_copy_with_consequence_for_the_tenant_owner(): void
    {
        $this->travelTo(Carbon::parse('2026-06-07'));
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->for($tenant)->create([
            'role' => UserRole::AgencyOwner,
            'email' => 'owner@falcon.test',
            'name' => 'Owner',
            'locale' => 'en',
        ]);

        $message = $this->app->make(AlertMessageBuilder::class)->build($this->makeAlert($tenant));

        $this->assertSame('owner@falcon.test', $message->recipientEmail);
        $this->assertStringContainsString('Trade Licence', $message->subject);
        $this->assertStringContainsString('Falcon LLC', $message->subject);
        $this->assertStringContainsString('The company record may be frozen.', $message->body);
        $this->assertSame('en', $message->locale);
    }

    public function test_responsible_user_takes_precedence_and_drives_locale(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->for($tenant)->create(['role' => UserRole::AgencyOwner, 'email' => 'owner@falcon.test']);
        $responsible = User::factory()->for($tenant)->create(['email' => 'staff@falcon.test', 'locale' => 'ar']);

        $message = $this->app->make(AlertMessageBuilder::class)->build($this->makeAlert($tenant, $responsible->id));

        $this->assertSame('staff@falcon.test', $message->recipientEmail);
        $this->assertSame('ar', $message->locale);
    }
}
