<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Active country / rule pack
    |--------------------------------------------------------------------------
    | Phase 1 ships the UAE pack active. The engine is GCC-ready; other country
    | packs are added as data, not code.
    */
    'active_country' => env('COMPLIANCE_ACTIVE_COUNTRY', 'AE'),
    'uae_rule_pack_version' => 'uae-2026.06',

    /*
    |--------------------------------------------------------------------------
    | Alert cadence (SPEC.md §8)
    |--------------------------------------------------------------------------
    | Days before a deadline's due date to fire alerts. This is the default;
    | a document type may override it via its `alert_offset_days` column.
    */
    'alert_lead_days' => [90, 60, 30, 7, 1],

    /*
    | Channels to schedule alerts on (comma-separated env, e.g. "email,whatsapp").
    | WhatsApp delivers via Twilio when configured (recipients need a phone on
    | file). Push is a future drop-in. Defaults to email only.
    */
    'alert_channels' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('COMPLIANCE_ALERT_CHANNELS', 'email'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | UAE public holidays (working-day calendar)
    |--------------------------------------------------------------------------
    | UAE weekend is Sat–Sun; the working week is Mon–Fri. Islamic-calendar
    | holidays (Eid al-Fitr, Eid al-Adha, etc.) shift each year — RE-VERIFY and
    | update annually against the official UAE holiday calendar. Dates below are
    | a starting baseline only.
    */
    'uae_public_holidays' => [
        '2026-01-01', // New Year's Day
        '2026-12-02', // UAE National Day
        '2026-12-03', // UAE National Day (holiday)
    ],

];
