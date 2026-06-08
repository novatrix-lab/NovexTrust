<?php

declare(strict_types=1);

namespace App\Extraction;

use App\Models\Document;

/**
 * Runs extraction over a document's contents and PRE-FILLS suggestions onto the
 * document. It marks the document `extracted` but never `confirmed`, and never
 * generates a deadline — that is gated behind the human-confirm step (M6).
 *
 * Pre-fill only fills empty fields, so human-entered values at upload are never
 * overwritten by a machine guess.
 */
final class ExtractionPipeline
{
    public function __construct(private readonly Extractor $extractor) {}

    public function run(Document $document, string $contents, string $mimeType): Document
    {
        $result = $this->extractor->extract($contents, $mimeType);

        $document->extracted_data = $result->toArray();

        if ($document->issue_date === null && $result->issueDate !== null) {
            $document->issue_date = $result->issueDate;
        }

        if ($document->expiry_date === null && $result->expiryDate !== null) {
            $document->expiry_date = $result->expiryDate;
        }

        $document->extracted = true;
        // Intentionally left UNconfirmed — no deadline until a human confirms.
        $document->save();

        return $document;
    }
}
