<?php

declare(strict_types=1);

namespace App\Consents;

use App\Models\Consent;
use App\Models\User;

/**
 * Captures lawful-basis / consent records (SPEC.md §10, PDPL-aware), including
 * the cross-border-transfer basis (SCCs) for data that may leave the UAE.
 */
final class ConsentService
{
    /**
     * Record the baseline consents captured at tenant onboarding.
     */
    public function recordOnboarding(User $user): void
    {
        $purposes = [
            ['purpose' => 'platform_terms', 'transfer_basis' => null],
            ['purpose' => 'document_processing', 'transfer_basis' => null],
            ['purpose' => 'cross_border_transfer', 'transfer_basis' => 'SCCs'],
        ];

        foreach ($purposes as $purpose) {
            Consent::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'purpose' => $purpose['purpose'],
                'granted_at' => now(),
                'transfer_basis' => $purpose['transfer_basis'],
            ]);
        }
    }
}
