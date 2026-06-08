<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Delivery status of a scheduled alert (SPEC.md §6 alerts.status).
 */
enum AlertStatus: string
{
    case Pending = 'pending';     // scheduled, not yet sent
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled'; // e.g. deadline marked done before send
}
