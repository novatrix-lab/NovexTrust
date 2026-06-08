<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle status of a stored document (SPEC.md §6 documents.status). Distinct
 * from the per-deadline status: this tracks the document itself, while
 * {@see DeadlineStatus} tracks each renewal obligation derived from it.
 */
enum DocumentStatus: string
{
    case Pending = 'pending';   // uploaded, awaiting human confirmation (M6)
    case Active = 'active';     // confirmed and current
    case Expired = 'expired';   // past its expiry date
    case Archived = 'archived'; // superseded or retired
}
