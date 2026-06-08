<?php

declare(strict_types=1);

namespace App\Documents;

use App\Enums\DocumentStatus;
use App\Jobs\ExtractDocument;
use App\Models\Document;
use App\Vault\DocumentVault;

/**
 * Stores an uploaded document's bytes in the encrypted vault and records the
 * Document row referencing the opaque vault key.
 *
 * A freshly uploaded document is always `pending` and UNconfirmed — no deadline
 * is generated until a human confirms the extracted data (M6). tenant_id is
 * stamped automatically from the request's tenant context.
 */
final class DocumentUploadService
{
    public function __construct(private readonly DocumentVault $vault) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(string $contents, array $attributes): Document
    {
        $fileRef = $this->vault->store($contents);

        $document = Document::create([
            ...$attributes,
            'file_ref' => $fileRef,
            'status' => DocumentStatus::Pending,
            'extracted' => false,
            'confirmed' => false,
        ]);

        // Extraction runs on the queue and re-fetches bytes from the vault.
        ExtractDocument::dispatch($document->id);

        // Reflect any synchronous extraction (local/sync queue) in the response.
        return $document->refresh();
    }
}
