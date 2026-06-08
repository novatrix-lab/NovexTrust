<?php

declare(strict_types=1);

namespace App\Vault\Kms;

/**
 * Abstraction over the key-management service (SPEC.md §10). Modelled on the
 * AWS KMS envelope pattern so a managed KMS/HSM can be dropped in later without
 * touching the vault. Implementations must keep the master key (KEK) separate
 * from the document data store and log key access.
 */
interface KeyManagementService
{
    /**
     * Identifier of the master key (KEK) in use — stored with each envelope so
     * the right key (and rotation generation) can be selected on decrypt.
     */
    public function keyId(): string;

    /**
     * Generate a new random data key, returning both its plaintext and the
     * KMS-wrapped (encrypted) form.
     */
    public function generateDataKey(): DataKey;

    /**
     * Unwrap a previously wrapped data key, returning the plaintext key. This is
     * a key-access event and must be logged by the implementation.
     */
    public function decryptDataKey(string $encryptedKey): string;
}
