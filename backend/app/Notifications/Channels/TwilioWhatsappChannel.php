<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Enums\AlertChannel;
use App\Notifications\AlertMessage;
use App\Notifications\NotificationChannel;
use App\Notifications\Whatsapp\WhatsappSender;
use RuntimeException;

/**
 * Real WhatsApp channel (Twilio). Used when Twilio credentials are configured;
 * otherwise the stub {@see WhatsappChannel} is registered instead.
 */
final class TwilioWhatsappChannel implements NotificationChannel
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
