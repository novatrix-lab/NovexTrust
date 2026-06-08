<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Extraction\ExtractionPipeline;
use App\Models\Document;
use App\Vault\DocumentVault;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued extraction (SPEC.md §11: extraction runs on the queue). The job carries
 * only the document id — the file bytes are re-fetched (decrypted) from the vault
 * here, so plaintext never travels through the queue payload.
 */
class ExtractDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $documentId) {}

    public function handle(DocumentVault $vault, ExtractionPipeline $pipeline): void
    {
        // No tenant context in a queued job; load across scope by id.
        $document = Document::withoutTenancy()->find($this->documentId);

        if ($document === null || $document->file_ref === null) {
            return;
        }

        $contents = $vault->retrieve($document->file_ref);
        $pipeline->run($document, $contents, (string) $document->mime_type);
    }
}
