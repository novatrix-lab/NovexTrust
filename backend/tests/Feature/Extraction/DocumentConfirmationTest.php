<?php

declare(strict_types=1);

namespace Tests\Feature\Extraction;

use App\Enums\DocumentStatus;
use App\Enums\RenewalCycle;
use App\Extraction\ExtractionResult;
use App\Extraction\Extractor;
use App\Extraction\StubExtractor;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The end-to-end M6 guarantee: extraction pre-fills, but NO deadline exists until
 * a human confirms (SPEC.md §8). A misread date never silently becomes a live
 * deadline.
 */
class DocumentConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj<<>>endobj\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('vault');
    }

    private function cannedExtractor(ExtractionResult $result): void
    {
        $this->app->instance(Extractor::class, new StubExtractor($result));
    }

    private function uploadFor(Tenant $tenant, DocumentType $type): array
    {
        $entity = Entity::factory()->for($tenant)->create();
        Sanctum::actingAs(User::factory()->for($tenant)->create());

        $response = $this->post('/api/documents', [
            'document_type_id' => $type->id,
            'entity_id' => $entity->id,
            'file' => UploadedFile::fake()->createWithContent('licence.pdf', self::PDF),
        ], ['Accept' => 'application/json']);

        return [$response, $entity];
    }

    public function test_upload_extracts_and_prefills_but_creates_no_deadline(): void
    {
        $this->cannedExtractor(new ExtractionResult(issueDate: '2026-05-01', expiryDate: '2027-06-01'));
        $tenant = Tenant::factory()->create();
        $type = DocumentType::factory()->create();

        [$response] = $this->uploadFor($tenant, $type);

        $response->assertCreated()
            ->assertJsonPath('data.extracted', true)
            ->assertJsonPath('data.confirmed', false)
            ->assertJsonPath('data.status', DocumentStatus::Pending->value);

        // Date was pre-filled from extraction.
        $document = Document::query()->findOrFail($response->json('data.id'));
        $this->assertSame('2027-06-01', $document->expiry_date->toDateString());

        // No deadline until confirmed.
        $this->assertDatabaseCount('deadlines', 0);
    }

    public function test_confirming_generates_the_deadline_from_verified_data(): void
    {
        $this->cannedExtractor(new ExtractionResult(expiryDate: '2027-06-01'));
        $tenant = Tenant::factory()->create();
        $type = DocumentType::factory()->create([
            'default_renewal_cycle' => RenewalCycle::Annual,
            'default_lead_days' => 90,
        ]);

        [$response] = $this->uploadFor($tenant, $type);
        $id = $response->json('data.id');

        // Human corrects the date and confirms.
        $confirm = $this->postJson("/api/documents/{$id}/confirm", [
            'expiry_date' => '2027-07-15',
        ]);

        $confirm->assertOk()
            ->assertJsonPath('data.confirmed', true)
            ->assertJsonPath('data.status', DocumentStatus::Active->value);

        $this->assertNotNull($confirm->json('data.confirmed_by'));
        $this->assertSame(1, $confirm->json('data.deadlines') === null ? 0 : count($confirm->json('data.deadlines')));
        $this->assertDatabaseHas('deadlines', [
            'document_id' => $id,
            'due_date' => '2027-07-15 00:00:00',
        ]);
    }

    public function test_cannot_confirm_another_tenants_document(): void
    {
        $this->cannedExtractor(new ExtractionResult(expiryDate: '2027-06-01'));
        $tenantB = Tenant::factory()->create();
        [$response] = $this->uploadFor($tenantB, DocumentType::factory()->create());
        $foreignId = $response->json('data.id');

        // Switch to another tenant.
        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/documents/{$foreignId}/confirm", ['expiry_date' => '2027-07-15'])
            ->assertNotFound();
    }
}
