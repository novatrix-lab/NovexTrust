<?php

declare(strict_types=1);

namespace App\Extraction;

/**
 * Structured fields returned by the extractor (SPEC.md §9): a document-type
 * guess, holder name, ID/licence number, and issue/expiry dates. Every field is
 * a SUGGESTION only — nothing here becomes a tracked deadline until a human
 * confirms it (the M6 confirm gate).
 */
final class ExtractionResult
{
    public function __construct(
        public readonly ?string $documentTypeGuess = null,
        public readonly ?string $holderName = null,
        public readonly ?string $idNumber = null,
        public readonly ?string $issueDate = null,   // Y-m-d
        public readonly ?string $expiryDate = null,  // Y-m-d
        public readonly ?float $confidence = null,
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'document_type_guess' => $this->documentTypeGuess,
            'holder_name' => $this->holderName,
            'id_number' => $this->idNumber,
            'issue_date' => $this->issueDate,
            'expiry_date' => $this->expiryDate,
            'confidence' => $this->confidence,
        ];
    }
}
