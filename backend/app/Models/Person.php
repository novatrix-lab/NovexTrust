<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PersonRole;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use Auditable, BelongsToTenant, HasFactory;

    // Eloquent would pluralise "Person" to "people"; our table is "persons".
    protected $table = 'persons';

    protected $fillable = [
        'tenant_id',
        'entity_id',
        'full_name',
        'role',
        'passport_no',
        'emirates_id_no',
    ];

    protected function casts(): array
    {
        return [
            'role' => PersonRole::class,
        ];
    }

    /**
     * @return BelongsTo<Entity, $this>
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
