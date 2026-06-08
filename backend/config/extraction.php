<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Extraction driver
    |--------------------------------------------------------------------------
    | The document-AI provider behind the Extractor interface (SPEC.md §9).
    | "Buy, don't build": a real driver is a zero-retention multimodal LLM or a
    | managed Document-AI service. `stub` ships now so the pipeline is testable
    | without a vendor; swap via this config once a vendor + zero-retention terms
    | are confirmed.
    |
    | MANDATORY for any real provider: zero data retention / no training on
    | customer data — inputs are passports and Emirates IDs.
    */
    'driver' => env('EXTRACTION_DRIVER', 'stub'),

];
