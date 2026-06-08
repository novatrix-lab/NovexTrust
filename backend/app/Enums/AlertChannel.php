<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Delivery channel for an alert (SPEC.md §6/§8). Push/FCM is designed in now as
 * a drop-in channel even though only email + WhatsApp ship in Phase 1.
 */
enum AlertChannel: string
{
    case Email = 'email';
    case Whatsapp = 'whatsapp';
    case Push = 'push';
}
