<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeadlineStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\DeadlineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deadline extends Model
{
    /** @use HasFactory<DeadlineFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'document_id',
        'due_date',
        'lead_days',
        'status',
        'responsible_user_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'lead_days' => 'integer',
            'status' => DeadlineStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
