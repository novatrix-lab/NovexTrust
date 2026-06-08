<?php

declare(strict_types=1);

namespace App\Vault\Kms;

/**
 * A freshly generated data-encryption key (DEK): the plaintext key used to
 * encrypt one document, plus the same key wrapped (encrypted) by the KMS master
 * key for storage. The plaintext form is held only in memory, transiently.
 */
final class DataKey
{
    public function __construct(
        public readonly string $plaintextKey,
        public readonly string $encryptedKey,
    ) {}
}
