<?php

declare(strict_types=1);

namespace App\Vault;

use App\Vault\Kms\KeyManagementService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * The encrypted document vault (SPEC.md §8/§10). Envelope encryption:
 *
 *   plaintext --AES-256-GCM(DEK)--> ciphertext
 *   DEK       --wrapped by KMS KEK--> encryptedKey
 *
 * The vault writes a single self-contained envelope (wrapped DEK + iv + tag +
 * ciphertext) to the storage disk under an OPAQUE key, and returns that key as
 * the document's `file_ref`. Plaintext is never written to disk; the KEK lives
 * in the KMS, separate from the data.
 */
final class DocumentVault
{
    private const CIPHER = 'aes-256-gcm';

    private const IV_LEN = 12;

    private const TAG_LEN = 16;

    private const ENVELOPE_VERSION = 1;

    public function __construct(
        private readonly KeyManagementService $kms,
        private readonly Filesystem $disk,
    ) {}

    /**
     * Encrypt and store contents. Returns the opaque vault key (file_ref).
     */
    public function store(string $contents): string
    {
        $dataKey = $this->kms->generateDataKey();
        $plaintextKey = $dataKey->plaintextKey; // working copy (DataKey is readonly)

        $iv = random_bytes(self::IV_LEN);
        $tag = '';
        $ciphertext = openssl_encrypt($contents, self::CIPHER, $plaintextKey, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LEN);

        if ($ciphertext === false) {
            throw new VaultException('Failed to encrypt document contents.');
        }

        $envelope = json_encode([
            'v' => self::ENVELOPE_VERSION,
            'kek_id' => $this->kms->keyId(),
            'alg' => self::CIPHER,
            'dek' => base64_encode($dataKey->encryptedKey),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($ciphertext),
        ], JSON_THROW_ON_ERROR);

        $ref = $this->newReference();
        $this->disk->put($ref, $envelope);

        $this->wipe($plaintextKey);

        return $ref;
    }

    /**
     * Retrieve and decrypt the contents stored under a vault key.
     */
    public function retrieve(string $fileRef): string
    {
        if (!$this->disk->exists($fileRef)) {
            throw new VaultException("Vault object [{$fileRef}] not found.");
        }

        /** @var array<string, mixed> $envelope */
        $envelope = json_decode((string) $this->disk->get($fileRef), true, 512, JSON_THROW_ON_ERROR);

        $dataKey = $this->kms->decryptDataKey(base64_decode($envelope['dek']));

        $plaintext = openssl_decrypt(
            base64_decode($envelope['data']),
            self::CIPHER,
            $dataKey,
            OPENSSL_RAW_DATA,
            base64_decode($envelope['iv']),
            base64_decode($envelope['tag']),
        );

        $this->wipe($dataKey);

        if ($plaintext === false) {
            throw new VaultException("Failed to decrypt vault object [{$fileRef}] (tampered or wrong key).");
        }

        return $plaintext;
    }

    public function exists(string $fileRef): bool
    {
        return $this->disk->exists($fileRef);
    }

    public function delete(string $fileRef): void
    {
        $this->disk->delete($fileRef);
    }

    /**
     * Opaque, sharded key — carries no tenant/document information.
     */
    private function newReference(): string
    {
        $id = Str::uuid()->getHex()->toString();

        return sprintf('vault/%s/%s/%s.enc', substr($id, 0, 2), substr($id, 2, 2), $id);
    }

    private function wipe(string &$key): void
    {
        if (function_exists('sodium_memzero')) {
            sodium_memzero($key);
        } else {
            $key = str_repeat("\0", strlen($key));
        }
    }
}
