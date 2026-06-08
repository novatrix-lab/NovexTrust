<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\AlertChannel;
use RuntimeException;

final class NotificationChannelRegistry
{
    /** @var array<string, NotificationChannel> */
    private array $channels = [];

    public function register(NotificationChannel $channel): void
    {
        $this->channels[$channel->channel()->value] = $channel;
    }

    public function for(AlertChannel $channel): NotificationChannel
    {
        return $this->channels[$channel->value]
            ?? throw new RuntimeException("No notification channel registered for [{$channel->value}].");
    }
}
