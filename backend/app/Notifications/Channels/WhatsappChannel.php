<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Enums\AlertChannel;
use App\Notifications\AlertMessage;
use App\Notifications\NotificationChannel;
use Illuminate\Support\Facades\Log;

/**
 * Stub WhatsApp channel (SPEC.md §14): WhatsApp Business API access is not yet
 * live. It logs instead of sending so the pipeline is exercisable. Replace the
 * body with the real API call once access is arranged — the interface stays the
 * same. (By default no WhatsApp alerts are generated; see config compliance.alert_channels.)
 */
final class WhatsappChannel implements NotificationChannel
{
    public function channel(): AlertChannel
    {
        return AlertChannel::Whatsapp;
    }

    public function send(AlertMessage $message): void
    {
        Log::info('notifications.whatsapp.stub', [
            'to' => $message->recipientPhone ?? '(no phone on file)',
            'subject' => $message->subject,
        ]);
    }
}
