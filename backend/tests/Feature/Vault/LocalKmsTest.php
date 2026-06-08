<?php

declare(strict_types=1);

namespace Tests\Feature\Vault;

use App\Vault\Kms\LocalKms;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class LocalKmsTest extends TestCase
{
    private function kms(): LocalKms
    {
        return new LocalKms('base64:'.base64_encode(random_bytes(32)));
    }

    public function test_generates_a_256_bit_data_key_and_wraps_it(): void
    {
        $kms = $this->kms();
        $dataKey = $kms->generateDataKey();

        $this->assertSame(32, strlen($dataKey->plaintextKey));
        $this->assertNotSame($dataKey->plaintextKey, $dataKey->encryptedKey);
    }

    public function test_unwrap_round_trips_the_data_key(): void
    {
        $kms = $this->kms();
        $dataKey = $kms->generateDataKey();

        $this->assertSame($dataKey->plaintextKey, $kms->decryptDataKey($dataKey->encryptedKey));
    }

    public function test_a_different_master_key_cannot_unwrap(): void
    {
        $wrapped = $this->kms()->generateDataKey()->encryptedKey;

        $this->expectException(RuntimeException::class);
        $this->kms()->decryptDataKey($wrapped); // different random master key
    }

    public function test_key_access_is_logged(): void
    {
        Log::spy();
        $kms = $this->kms();
        $dataKey = $kms->generateDataKey();

        $kms->decryptDataKey($dataKey->encryptedKey);

        Log::shouldHaveReceived('info')->withArgs(
            fn (string $message): bool => $message === 'vault.kms.unwrap'
        )->once();
    }

    public function test_missing_master_key_throws(): void
    {
        $this->expectException(RuntimeException::class);
        new LocalKms(null);
    }
}
