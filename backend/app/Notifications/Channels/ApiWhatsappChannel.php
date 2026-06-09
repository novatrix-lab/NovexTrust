<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Enums\AlertChannel;
use App\Notifications\AlertMessage;
use App\Notifications\NotificationChannel;
use App\Notifications\Whatsapp\WhatsappSender;
use RuntimeException;

/**
 * Real WhatsApp channel. Provider-agnostic — it delegates to whichever
 * {@see WhatsappSender} is bound (Twilio or Meta Cloud API). Used when a
 * provider is configured; otherwise the no-op {@see WhatsappChannel} stub runs.
 */
final class ApiWhatsappChannel implements NotificationChannel
{
    public function __construct(private readonly WhatsappSender $sender) {}

    public function channel(): AlertChannel
    {
        return AlertChannel::Whatsapp;
    }

    public function send(AlertMessage $message): void
    {
        if (blank($message->recipientPhone)) {
            throw new RuntimeException('No WhatsApp number on file for the alert recipient.');
        }

        $this->sender->send($message->recipientPhone, $message->body);
    }
}
