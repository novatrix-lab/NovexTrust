<?php

declare(strict_types=1);

namespace App\Vault\Kms;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Local KMS: wraps data keys with a master key held in the environment
 * (VAULT_MASTER_KEY), kept separate from the document store. Suitable for dev
 * and single-server deployments. For managed key custody, implement an AwsKms
 * against {@see KeyManagementService} and switch via config('vault.driver').
 *
 * Wrapping uses AES-256-GCM (authenticated): the wrapped blob is iv|tag|ciphertext.
 */
final class LocalKms implements KeyManagementService
{
    private const CIPHER = 'aes-256-gcm';

    private const IV_LEN = 12;

    private const TAG_LEN = 16;

    private readonly string $masterKey;

    public function __construct(?string $masterKey, private readonly string $kekId = 'local-master-v1')
    {
        $this->masterKey = $this->decodeMasterKey($masterKey);
    }

    public function keyId(): string
    {
        return $this->kekId;
    }

    public function generateDataKey(): DataKey
    {
        $plaintext = random_bytes(32); // 256-bit DEK

        return new DataKey($plaintext, $this->wrap($plaintext));
    }

    public function decryptDataKey(string $encryptedKey): string
    {
        // Key-access event — logged for the audit trail (SPEC.md §10). No secret
        // material is logged, only the KEK id.
        Log::info('vault.kms.unwrap', ['kek_id' => $this->kekId]);

        return $this->unwrap($encryptedKey);
    }

    private function wrap(string $dataKey): string
    {
        $iv = random_bytes(self::IV_LEN);
        $tag = '';
        $ciphertext = openssl_encrypt($dataKey, self::CIPHER, $this->masterKey, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LEN);

        if ($ciphertext === false) {
            throw new RuntimeException('Failed to wrap data key.');
        }

        return $iv.$tag.$ciphertext;
    }

    private function unwrap(string $blob): string
    {
        $iv = substr($blob, 0, self::IV_LEN);
        $tag = substr($blob, self::IV_LEN, self::TAG_LEN);
        $ciphertext = substr($blob, self::IV_LEN + self::TAG_LEN);

        $dataKey = openssl_decrypt($ciphertext, self::CIPHER, $this->masterKey, OPENSSL_RAW_DATA, $iv, $tag);

        if ($dataKey === false) {
            throw new RuntimeException('Failed to unwrap data key (wrong master key or tampered ciphertext).');
        }

        return $dataKey;
    }

    private function decodeMasterKey(?string $key): string
    {
        if (blank($key)) {
            throw new RuntimeException('VAULT_MASTER_KEY is not set. Run `php artisan vault:key`.');
        }

        if (str_starts_with($key, 'base64:')) {
            $key = substr($key, 7);
        }

        $decoded = base64_decode($key, true);

        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException('VAULT_MASTER_KEY must be 32 bytes, base64-encoded.');
        }

        return $decoded;
    }
}
