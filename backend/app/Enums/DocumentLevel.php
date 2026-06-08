<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Whether a document type attaches to a business entity or to a person
 * (SPEC.md §7 distinguishes entity-level vs person-level documents). Drives
 * where a document can be filed and how deadlines are generated (M4).
 */
enum DocumentLevel: string
{
    case Entity = 'entity';
    case Person = 'person';
}
