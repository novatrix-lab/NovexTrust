<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Document type category (SPEC.md §6 document_types.category).
 */
enum DocumentCategory: string
{
    case License = 'license';
    case Immigration = 'immigration';
    case Tax = 'tax';
    case Tenancy = 'tenancy';
    case Employee = 'employee';
    case Other = 'other';
}
