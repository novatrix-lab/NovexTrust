<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Vault storage disk
    |--------------------------------------------------------------------------
    | Where encrypted document envelopes are written. Local for dev; point this
    | at an S3-compatible disk in production. Storage location is configuration
    | so data residency can be changed without re-architecting (SPEC.md §10).
    | The app DB only ever stores an opaque file_ref into this disk.
    */
    'disk' => env('VAULT_DISK', 'vault'),

    /*
    |--------------------------------------------------------------------------
    | Key management service (KMS)
    |--------------------------------------------------------------------------
    | The master key (KEK) is held by the KMS, SEPARATE from the data store
    | (SPEC.md §10 — keys are the crown jewels). `local` wraps data keys with a
    | master key from the environment; `aws` (a future drop-in) would call AWS
    | KMS. Generate a local key with `php artisan vault:key`.
    */
    'driver' => env('VAULT_KMS_DRIVER', 'local'),
    'master_key' => env('VAULT_MASTER_KEY'),
    'kek_id' => env('VAULT_KEK_ID', 'local-master-v1'),

];
