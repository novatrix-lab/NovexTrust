<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generates a 32-byte base64 master key for the local KMS (VAULT_MASTER_KEY).
 * Mirrors `key:generate` for the vault's crown-jewel key.
 */
class GenerateVaultKey extends Command
{
    protected $signature = 'vault:key {--show : Display the key instead of writing it to .env}';

    protected $description = 'Generate the vault KMS master key (VAULT_MASTER_KEY)';

    public function handle(): int
    {
        $key = 'base64:'.base64_encode(random_bytes(32));

        if ($this->option('show')) {
            $this->line($key);

            return self::SUCCESS;
        }

        $path = base_path('.env');

        if (!file_exists($path)) {
            $this->error('.env not found. Re-run with --show and set VAULT_MASTER_KEY manually.');

            return self::FAILURE;
        }

        $contents = (string) file_get_contents($path);

        if (preg_match('/^VAULT_MASTER_KEY=.*$/m', $contents) === 1) {
            $contents = preg_replace('/^VAULT_MASTER_KEY=.*$/m', 'VAULT_MASTER_KEY='.$key, $contents);
        } else {
            $contents .= PHP_EOL.'VAULT_MASTER_KEY='.$key.PHP_EOL;
        }

        file_put_contents($path, $contents);
        $this->info('Vault master key set in .env.');

        return self::SUCCESS;
    }
}
