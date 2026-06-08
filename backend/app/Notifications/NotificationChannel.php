<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\AlertChannel;

/**
 * A delivery channel for alerts (SPEC.md §8). Email ships real; WhatsApp is a
 * stub until Business API access is live; push/FCM is a future drop-in — each is
 * just another implementation registered with the {@see NotificationChannelRegistry}.
 *
 * Implementations throw on delivery failure so the sender can record it.
 */
interface NotificationChannel
{
    public function channel(): AlertChannel;

    public function send(AlertMessage $message): void;
}
