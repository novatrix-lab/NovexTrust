<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Composes the localized alert copy for a deadline (SPEC.md §8), including the
 * consequence note so urgency is clear. Recipient is the deadline's responsible
 * user, otherwise the tenant owner. All strings come from lang files (no
 * hardcoded copy), with the recipient's locale (en/ar).
 */
final class AlertMessageBuilder
{
    public function build(Alert $alert): AlertMessage
    {
        $alert->loadMissing([
            'deadline.document.documentType',
            'deadline.document.entity',
            'deadline.document.person',
            'deadline.responsibleUser',
        ]);

        $deadline = $alert->deadline;
        $document = $deadline->document;
        $type = $document->documentType;

        $recipient = $deadline->responsibleUser ?? $this->tenantOwner($alert->tenant_id);
        $locale = $recipient?->locale ?? (string) config('app.fallback_locale', 'en');

        $subjectName = $document->entity?->legal_name
            ?? $document->person?->full_name
            ?? trans('alerts.your_organisation', [], $locale);

        $dueDate = Carbon::instance($deadline->due_date);
        $days = (int) Carbon::today()->diffInDays($dueDate, false);
        $overdue = $days < 0;

        $replace = [
            'document' => $type->name,
            'name' => $subjectName,
            'date' => $dueDate->toFormattedDateString(),
            'days' => abs($days),
        ];

        $subject = trans($overdue ? 'alerts.subject_overdue' : 'alerts.subject', $replace, $locale);

        $body = implode("\n\n", array_filter([
            trans('alerts.greeting', ['name' => $recipient?->name ?? $subjectName], $locale),
            trans($overdue ? 'alerts.body_overdue' : 'alerts.body_due', $replace, $locale),
            $type->consequence_note
                ? trans('alerts.consequence', ['note' => $type->consequence_note], $locale)
                : null,
            trans('alerts.footer', ['app' => config('app.name')], $locale),
        ]));

        return new AlertMessage(
            subject: $subject,
            body: $body,
            recipientName: $recipient?->name ?? $subjectName,
            recipientEmail: $recipient?->email,
            recipientPhone: null, // no phone column yet; WhatsApp is stubbed
            locale: $locale,
        );
    }

    private function tenantOwner(?int $tenantId): ?User
    {
        if ($tenantId === null) {
            return null;
        }

        $base = User::withoutTenancy()->where('tenant_id', $tenantId);

        return (clone $base)
            ->whereIn('role', [UserRole::AgencyOwner->value, UserRole::SmeOwner->value])
            ->first()
            ?? $base->orderBy('id')->first();
    }
}
