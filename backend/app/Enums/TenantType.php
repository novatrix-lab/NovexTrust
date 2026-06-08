<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tenant types (SPEC.md §6). An agency manages many client entities; an SME
 * typically holds one or a few of its own.
 */
enum TenantType: string
{
    case Agency = 'agency';
    case Sme = 'sme';

    /**
     * The role assigned to the user who registers (and thereby owns) a tenant
     * of this type.
     */
    public function ownerRole(): UserRole
    {
        return match ($this) {
            self::Agency => UserRole::AgencyOwner,
            self::Sme => UserRole::SmeOwner,
        };
    }
}
