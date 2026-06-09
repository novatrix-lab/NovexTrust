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

    /** Tailwind classes for the traffic-light pill. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Safe => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::DueSoon => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::Overdue => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            self::Done => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-500/20',
        };
    }

    /** Dot colour for the pill / accents. */
    public function dotClass(): string
    {
        return match ($this) {
            self::Safe => 'bg-emerald-500',
            self::DueSoon => 'bg-amber-500',
            self::Overdue => 'bg-rose-500',
            self::Done => 'bg-slate-400',
        };
    }

    /** Accent classes for the dashboard stat card. */
    public function cardAccent(): string
    {
        return match ($this) {
            self::Safe => 'text-emerald-600 bg-emerald-50',
            self::DueSoon => 'text-amber-600 bg-amber-50',
            self::Overdue => 'text-rose-600 bg-rose-50',
            self::Done => 'text-slate-500 bg-slate-100',
        };
    }
}
