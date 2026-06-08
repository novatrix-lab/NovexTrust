<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AlertChannel;
use App\Enums\AlertStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'deadline_id',
        'channel',
        'scheduled_for',
        'sent_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'channel' => AlertChannel::class,
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'status' => AlertStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Deadline, $this>
     */
    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class);
    }
}
