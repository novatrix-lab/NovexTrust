<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\TenantType;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class TenantTypeTest extends TestCase
{
    public function test_agency_owner_role_is_agency_owner(): void
    {
        $this->assertSame(UserRole::AgencyOwner, TenantType::Agency->ownerRole());
    }

    public function test_sme_owner_role_is_sme_owner(): void
    {
        $this->assertSame(UserRole::SmeOwner, TenantType::Sme->ownerRole());
    }
}
