<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\JurisdictionType;
use App\Models\Entity;

/**
 * Bulk-imports client entities from CSV — critical for agency onboarding
 * (SPEC.md §8: a firm will not hand-key 200 clients). Entities are tenant-scoped:
 * tenant_id is stamped automatically from the current context.
 *
 * Expected header columns (order-independent):
 *   legal_name (required), trade_name, jurisdiction_type, authority, license_number
 */
final class EntityCsvImporter
{
    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function import(string $csv): array
    {
        $rows = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $csv) ?: [])));

        if ($rows === []) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Empty file.']];
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), str_getcsv(array_shift($rows)));
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $line) {
            $cells = str_getcsv($line);
            $row = $this->associate($header, $cells);
            $legalName = trim((string) ($row['legal_name'] ?? ''));

            if ($legalName === '') {
                $skipped++;
                $errors[] = 'Row '.($index + 2).': missing legal_name.';

                continue;
            }

            Entity::create([
                'legal_name' => $legalName,
                'trade_name' => $this->nullable($row['trade_name'] ?? null),
                'jurisdiction_type' => $this->jurisdiction($row['jurisdiction_type'] ?? null),
                'authority' => $this->nullable($row['authority'] ?? null),
                'license_number' => $this->nullable($row['license_number'] ?? null),
                'country' => 'AE',
            ]);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @param  list<string>  $header
     * @param  list<string|null>  $cells
     * @return array<string, string|null>
     */
    private function associate(array $header, array $cells): array
    {
        $row = [];
        foreach ($header as $i => $key) {
            $row[$key] = $cells[$i] ?? null;
        }

        return $row;
    }

    private function nullable(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    private function jurisdiction(?string $value): ?string
    {
        $value = $value !== null ? strtolower(trim($value)) : null;

        return JurisdictionType::tryFrom((string) $value)?->value;
    }
}
