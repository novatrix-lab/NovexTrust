<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JurisdictionType;
use Database\Factories\RulePackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A versioned, country-level set of document types + deadline logic. GLOBAL
 * (not tenant-owned): shared by all tenants. Only the UAE pack is active in
 * Phase 1 (SPEC.md §7).
 */
class RulePack extends Model
{
    /** @use HasFactory<RulePackFactory> */
    use HasFactory;

    protected $fillable = [
        'country',
        'version',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<DocumentType, $this>
     */
    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class);
    }

    /**
     * Resolve a document type by code, preferring a jurisdiction-specific
     * variant (mainland/freezone) over the generic (any) one. This is how
     * per-jurisdiction rule variation is expressed in data, not code (SPEC.md §7).
     */
    public function documentType(string $code, ?JurisdictionType $jurisdiction = null): ?DocumentType
    {
        $candidates = $this->documentTypes()
            ->where('code', $code)
            ->where(function ($query) use ($jurisdiction): void {
                $query->whereNull('jurisdiction');
                if ($jurisdiction !== null) {
                    $query->orWhere('jurisdiction', $jurisdiction->value);
                }
            })
            ->get();

        // Prefer an exact jurisdiction match; fall back to the generic (null) one.
        return $candidates->firstWhere('jurisdiction', $jurisdiction)
            ?? $candidates->firstWhere('jurisdiction', null);
    }

    public static function activeFor(string $country): ?self
    {
        return self::query()->where('country', $country)->where('active', true)->first();
    }
}
