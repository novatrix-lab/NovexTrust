<?php

declare(strict_types=1);

namespace App\Extraction;

/**
 * Placeholder extractor used until a zero-retention vendor is wired in. It does
 * NOT invent data — by default it returns an empty result (the human fills the
 * form). A canned result can be injected for local demos and tests so the
 * pre-fill + confirm pipeline is exercisable without a vendor.
 */
final class StubExtractor implements Extractor
{
    public function __construct(private readonly ?ExtractionResult $canned = null) {}

    public function extract(string $contents, string $mimeType): ExtractionResult
    {
        return $this->canned ?? ExtractionResult::empty();
    }
}
