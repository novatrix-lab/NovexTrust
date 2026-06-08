<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * UAE business jurisdiction (SPEC.md §6 entities.jurisdiction). The specific
 * authority (DED, DMCC, JAFZA, …) is stored alongside as a free string, since
 * rules can vary by authority within each type (SPEC.md §7).
 */
enum JurisdictionType: string
{
    case Mainland = 'mainland';
    case Freezone = 'freezone';
}
