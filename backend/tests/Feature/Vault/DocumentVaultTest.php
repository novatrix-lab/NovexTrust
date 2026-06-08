<?php

declare(strict_types=1);

namespace Tests\Feature\Vault;

use App\Vault\DocumentVault;
use App\Vault\Kms\LocalKms;
use App\Vault\VaultException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentVaultTest extends TestCase
{
    private Filesystem $disk;

    private DocumentVault $vault;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disk = Storage::fake('vault');
        $this->vault = new DocumentVault(
            new LocalKms('base64:'.base64_encode(random_bytes(32))),
            $this->disk,
        );
    }

    public function test_round_trips_contents(): void
    {
        $plaintext = 'Passport No. X1234567 — Emirates ID 784-1990-1234567-1';

        $ref = $this->vault->store($plaintext);

        $this->assertSame($plaintext, $this->vault->retrieve($ref));
    }

    public function test_never_writes_plaintext_to_disk(): void
    {
        $secret = 'TOP-SECRET-PASSPORT-NUMBER-998877';

        $ref = $this->vault->store($secret);

        $this->disk->assertExists($ref);
        $stored = (string) $this->disk->get($ref);
        $this->assertStringNotContainsString($secret, $stored);
        $this->assertStringNotContainsString('998877', $stored);
        // The opaque ref leaks no tenant/document info.
        $this->assertStringStartsWith('vault/', $ref);
        $this->assertStringEndsWith('.enc', $ref);
    }

    public function test_two_stores_use_distinct_keys_and_references(): void
    {
        $a = $this->vault->store('same contents');
        $b = $this->vault->store('same contents');

        $this->assertNotSame($a, $b);
        // Distinct random DEK + IV → distinct ciphertext for identical input.
        $this->assertNotSame($this->disk->get($a), $this->disk->get($b));
    }

    public function test_tampered_envelope_fails_to_decrypt(): void
    {
        $ref = $this->vault->store('integrity matters');

        $envelope = json_decode((string) $this->disk->get($ref), true);
        $envelope['data'] = base64_encode(base64_decode($envelope['data']).'x'); // tamper
        $this->disk->put($ref, json_encode($envelope));

        $this->expectException(VaultException::class);
        $this->vault->retrieve($ref);
    }

    public function test_retrieving_a_missing_object_throws(): void
    {
        $this->expectException(VaultException::class);
        $this->vault->retrieve('vault/zz/zz/does-not-exist.enc');
    }

    public function test_delete_removes_the_object(): void
    {
        $ref = $this->vault->store('delete me');

        $this->vault->delete($ref);

        $this->assertFalse($this->vault->exists($ref));
    }
}
