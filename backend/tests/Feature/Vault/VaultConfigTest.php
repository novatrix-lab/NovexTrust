<?php

declare(strict_types=1);

namespace Tests\Feature\Vault;

use Tests\TestCase;

/**
 * Guards the crown-jewel disk configuration: the vault must never be public and
 * must fail loudly (so a write/read failure can't silently drop documents).
 */
class VaultConfigTest extends TestCase
{
    public function test_vault_disk_is_private_and_fails_loud(): void
    {
        $disk = config('filesystems.disks.vault');

        $this->assertSame('private', $disk['visibility']);
        $this->assertTrue($disk['throw']);
    }

    public function test_vault_disk_driver_is_configurable(): void
    {
        // Defaults to local; production sets VAULT_DISK_DRIVER=s3.
        $this->assertSame('local', config('filesystems.disks.vault.driver'));
        $this->assertContains(config('vault.disk'), ['vault']);
    }
}
