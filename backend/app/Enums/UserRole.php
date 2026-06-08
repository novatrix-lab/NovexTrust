<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Concerns\BelongsToTenant;

/**
 * Application roles (SPEC.md §3).
 *
 * PlatformAdmin is cross-tenant (the founder/operator). All other roles are
 * scoped to a single tenant via {@see BelongsToTenant}.
 */
enum UserRole: string
{
    case PlatformAdmin = 'platform_admin';
    case AgencyOwner = 'agency_owner';
    case AgencyStaff = 'agency_staff';
    case SmeOwner = 'sme_owner';
    case Employee = 'employee';

    public function isPlatformAdmin(): bool
    {
        return $this === self::PlatformAdmin;
    }

    /**
     * Roles that own (rather than are scoped within) a tenant.
     */
    public function isTenantOwner(): bool
    {
        return $this === self::AgencyOwner || $this === self::SmeOwner;
    }
}
