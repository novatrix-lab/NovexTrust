<?php

declare(strict_types=1);

namespace Tests\Feature\Vault;

use App\Enums\DocumentStatus;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    /** Minimal valid-looking PDF carrying a secret marker we can search for. */
    private const PDF = "%PDF-1.4\n1 0 obj<<>>endobj\nSECRET-BYTES-1234\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('vault');
    }

    private function pdf(): File
    {
        return UploadedFile::fake()->createWithContent('passport.pdf', self::PDF);
    }

    public function test_upload_encrypts_to_the_vault_and_creates_a_pending_document(): void
    {
        $tenant = Tenant::factory()->create();
        $entity = Entity::factory()->for($tenant)->create();
        $type = DocumentType::factory()->create();
        Sanctum::actingAs(User::factory()->for($tenant)->create());

        $response = $this->post('/api/documents', [
            'document_type_id' => $type->id,
            'entity_id' => $entity->id,
            'file' => $this->pdf(),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.status', DocumentStatus::Pending->value)
            ->assertJsonPath('data.confirmed', false)
            ->assertJsonPath('data.tenant_id', $tenant->id);

        $fileRef = $response->json('data.file_ref');
        $this->assertNotNull($fileRef);

        // Exactly one encrypted envelope on disk, and it does NOT contain plaintext.
        $files = Storage::disk('vault')->allFiles();
        $this->assertCount(1, $files);
        $this->assertStringNotContainsString('SECRET-BYTES-1234', (string) Storage::disk('vault')->get($files[0]));
    }

    public function test_download_returns_the_original_bytes(): void
    {
        $tenant = Tenant::factory()->create();
        $entity = Entity::factory()->for($tenant)->create();
        Sanctum::actingAs(User::factory()->for($tenant)->create());

        $id = $this->post('/api/documents', [
            'document_type_id' => DocumentType::factory()->create()->id,
            'entity_id' => $entity->id,
            'file' => $this->pdf(),
        ], ['Accept' => 'application/json'])->json('data.id');

        $response = $this->get("/api/documents/{$id}/download");

        $response->assertOk();
        $this->assertSame(self::PDF, $response->content());
    }

    public function test_download_is_tenant_scoped(): void
    {
        // Tenant B uploads a document.
        $tenantB = Tenant::factory()->create();
        $entityB = Entity::factory()->for($tenantB)->create();
        Sanctum::actingAs(User::factory()->for($tenantB)->create());
        $foreignId = $this->post('/api/documents', [
            'document_type_id' => DocumentType::factory()->create()->id,
            'entity_id' => $entityB->id,
            'file' => $this->pdf(),
        ], ['Accept' => 'application/json'])->json('data.id');

        // Tenant A cannot download it.
        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/documents/{$foreignId}/download")->assertNotFound();
    }

    public function test_cannot_attach_to_another_tenants_entity(): void
    {
        $foreignEntity = Entity::factory()->create(); // some other tenant
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/documents', [
            'document_type_id' => DocumentType::factory()->create()->id,
            'entity_id' => $foreignEntity->id,
            'file' => $this->pdf(),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('entity_id');
    }

    public function test_requires_an_entity_or_a_person(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/documents', [
            'document_type_id' => DocumentType::factory()->create()->id,
            'file' => $this->pdf(),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('entity_id');
    }
}
