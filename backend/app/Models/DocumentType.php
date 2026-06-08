<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Enums\DocumentLevel;
use App\Enums\JurisdictionType;
use App\Enums\RenewalCycle;
use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single compliance document type within a rule pack (the moat data, SPEC.md
 * §7). GLOBAL (not tenant-owned). Carries the defaults the engine uses to
 * generate deadlines: renewal cycle, lead days, and the consequence note shown
 * in alert copy.
 */
class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'rule_pack_id',
        'country',
        'code',
        'name',
        'category',
        'level',
        'jurisdiction',
        'default_renewal_cycle',
        'default_lead_days',
        'fixed_due_date',
        'business_day_offset',
        'alert_offset_days',
        'consequence_note',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'level' => DocumentLevel::class,
            'jurisdiction' => JurisdictionType::class,
            'default_renewal_cycle' => RenewalCycle::class,
            'default_lead_days' => 'integer',
            'fixed_due_date' => 'date',
            'business_day_offset' => 'integer',
            'alert_offset_days' => 'array',
        ];
    }

    /**
     * @return BelongsTo<RulePack, $this>
     */
    public function rulePack(): BelongsTo
    {
        return $this->belongsTo(RulePack::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
