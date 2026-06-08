<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A stored document. `file_ref` is the opaque vault key (populated in M5);
 * `extracted` and `confirmed` are deliberately separate (M6 human-confirm step).
 */
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'entity_id',
        'person_id',
        'document_type_id',
        'issue_date',
        'expiry_date',
        'status',
        'file_ref',
        'mime_type',
        'original_filename',
        'extracted',
        'extracted_data',
        'confirmed',
        'confirmed_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'status' => DocumentStatus::class,
            'extracted' => 'boolean',
            'extracted_data' => 'array',
            'confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
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
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * @return HasMany<Deadline, $this>
     */
    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class);
    }
}
