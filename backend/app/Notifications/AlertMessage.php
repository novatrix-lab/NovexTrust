<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * Channel-agnostic, fully-composed alert content. The builder localises and
 * assembles it (including the consequence note); channels just deliver it.
 */
final class AlertMessage
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly string $recipientName,
        public readonly ?string $recipientEmail,
        public readonly ?string $recipientPhone,
        public readonly string $locale,
    ) {}
}
