<?php

declare(strict_types=1);

namespace App\Notifications\Whatsapp;

/**
 * Thin transport seam over the WhatsApp provider so the channel logic is
 * testable without hitting Twilio. `$toPhone` is an E.164 number (e.g. +9715…).
 */
interface WhatsappSender
{
    public function send(string $toPhone, string $body): void;
}
