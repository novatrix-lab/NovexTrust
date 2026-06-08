<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JurisdictionType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\EntityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    /** @use HasFactory<EntityFactory> */
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'legal_name',
        'trade_name',
        'jurisdiction_type',
        'authority',
        'license_number',
        'country',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'jurisdiction_type' => JurisdictionType::class,
        ];
    }

    /**
     * @return HasMany<Person, $this>
     */
    public function persons(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
