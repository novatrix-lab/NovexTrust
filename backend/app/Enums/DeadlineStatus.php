<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Traffic-light status of a deadline (SPEC.md §6 deadlines.status). Recomputed
 * daily by the scheduler (M7) and surfaced in the cockpit dashboard (M9).
 */
enum DeadlineStatus: string
{
    case Safe = 'safe';
    case DueSoon = 'due_soon';
    case Overdue = 'overdue';
    case Done = 'done';

    /** Translated label for the cockpit. */
    public function label(): string
    {
        return (string) __('cockpit.status.'.$this->value);
    }

    /** Tailwind classes for the traffic-light badge. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Safe => 'bg-green-100 text-green-800',
            self::DueSoon => 'bg-amber-100 text-amber-800',
            self::Overdue => 'bg-red-100 text-red-800',
            self::Done => 'bg-gray-100 text-gray-600',
        };
    }
}
