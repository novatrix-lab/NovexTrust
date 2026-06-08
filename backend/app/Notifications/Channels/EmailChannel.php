<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Enums\AlertChannel;
use App\Mail\AlertMail;
use App\Notifications\AlertMessage;
use App\Notifications\NotificationChannel;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

final class EmailChannel implements NotificationChannel
{
    public function channel(): AlertChannel
    {
        return AlertChannel::Email;
    }

    public function send(AlertMessage $message): void
    {
        if (blank($message->recipientEmail)) {
            throw new RuntimeException('No email address for alert recipient.');
        }

        Mail::to($message->recipientEmail)
            ->locale($message->locale)
            ->send(new AlertMail($message));
    }
}
