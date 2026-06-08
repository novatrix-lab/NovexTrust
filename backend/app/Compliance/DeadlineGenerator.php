<?php

declare(strict_types=1);

namespace App\Compliance;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\Deadline;
use App\Models\Document;
use App\Models\DocumentType;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Generates a deadline (and its alert schedule) from a confirmed document.
 *
 * Safety invariant (SPEC.md §8): a deadline may only be generated from a
 * CONFIRMED document — a misread extracted date must never silently become a
 * live deadline. Calling this on an unconfirmed document throws.
 *
 * Due-date resolution is fully data-driven (the rule pack owns the dates):
 *   1. business_day_offset  → issue + N UAE business days (FTA 20-day trigger)
 *   2. document expiry_date → renewable documents
 *   3. type fixed_due_date  → fixed-calendar milestones (e-invoicing)
 *   4. issue + renewal cycle interval (when expiry is unknown)
 */
final class DeadlineGenerator
{
    public function __construct(
        private readonly RenewalCalculator $renewals,
        private readonly DeadlineStatusCalculator $status,
        private readonly UaeWorkingDayCalendar $calendar,
    ) {}

    public function generate(Document $document): ?Deadline
    {
        if (!$document->confirmed) {
            throw new InvalidArgumentException(
                "Refusing to generate a deadline from unconfirmed document [{$document->id}].",
            );
        }

        $type = $document->documentType;
        $dueDate = $this->resolveDueDate($document, $type);

        if ($dueDate === null) {
            return null; // no determinable deadline (e.g. one-time with no date)
        }

        $leadDays = $type->default_lead_days;

        $deadline = Deadline::updateOrCreate(
            // Use the Carbon value (not a date string) so the lookup matches the
            // stored datetime format and regeneration stays idempotent.
            ['document_id' => $document->id, 'due_date' => $dueDate->startOfDay()],
            [
                'tenant_id' => $document->tenant_id,
                'lead_days' => $leadDays,
                'status' => $this->status->calculate($dueDate, $leadDays),
            ],
        );

        $this->scheduleAlerts($deadline, $dueDate, $type);

        return $deadline;
    }

    private function resolveDueDate(Document $document, DocumentType $type): ?CarbonImmutable
    {
        if ($type->business_day_offset !== null && $document->issue_date !== null) {
            return $this->calendar->addBusinessDays($document->issue_date, $type->business_day_offset);
        }

        if ($document->expiry_date !== null) {
            return CarbonImmutable::instance($document->expiry_date);
        }

        if ($type->fixed_due_date !== null) {
            return CarbonImmutable::instance($type->fixed_due_date);
        }

        if ($document->issue_date !== null) {
            return $this->renewals->nextDueDate($document->issue_date, $type->default_renewal_cycle);
        }

        return null;
    }

    private function scheduleAlerts(Deadline $deadline, CarbonImmutable $dueDate, DocumentType $type): void
    {
        $offsets = $type->alert_offset_days ?? config('compliance.alert_lead_days', []);
        $channels = config('compliance.alert_channels', []);

        foreach ($offsets as $offset) {
            $scheduledFor = $dueDate->subDays((int) $offset);

            // Never schedule an alert in the past (e.g. the 90-day warning for a
            // document confirmed only 40 days before it expires).
            if ($scheduledFor->isPast()) {
                continue;
            }

            foreach ($channels as $channel) {
                Alert::updateOrCreate(
                    [
                        'deadline_id' => $deadline->id,
                        'channel' => $channel,
                        'scheduled_for' => $scheduledFor,
                    ],
                    [
                        'tenant_id' => $deadline->tenant_id,
                        'status' => AlertStatus::Pending,
                    ],
                );
            }
        }
    }
}
