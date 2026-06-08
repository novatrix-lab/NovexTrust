<?php

declare(strict_types=1);

namespace App\Extraction;

/**
 * Abstraction over the bought document-AI service (SPEC.md §9). Swap the
 * implementation (stub → managed Document-AI / zero-retention LLM) without
 * touching the pipeline. The provider MUST offer zero data retention.
 */
interface Extractor
{
    public function extract(string $contents, string $mimeType): ExtractionResult;
}
