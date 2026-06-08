<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Default renewal cadence for a document type (SPEC.md §6/§7). The mapping from
 * cycle to a concrete interval lives with the deadline-generation engine (M4)
 * so it stays in one place and remains rule-pack driven.
 */
enum RenewalCycle: string
{
    case Annual = 'annual';
    case SemiAnnual = 'semi_annual';
    case Quarterly = 'quarterly';
    case Monthly = 'monthly';
    case Biennial = 'biennial';
    case Triennial = 'triennial';
    case OneTime = 'one_time';
    case Custom = 'custom';
}
