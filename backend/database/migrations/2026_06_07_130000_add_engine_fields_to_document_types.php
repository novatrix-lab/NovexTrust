<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Engine fields so the deadline-generation logic stays data-driven (dates and
 * cadences live in the rule pack, never hardcoded in PHP — SPEC.md §7,
 * uae-rule-data.md modelling notes):
 *
 *  - fixed_due_date       — fixed-calendar milestones (e-invoicing ASP/go-live)
 *  - business_day_offset  — due = issue + N UAE business days (FTA 20-day trigger)
 *  - alert_offset_days    — per-type alert cadence override (else config default)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->date('fixed_due_date')->nullable()->after('default_lead_days');
            $table->unsignedSmallInteger('business_day_offset')->nullable()->after('fixed_due_date');
            $table->json('alert_offset_days')->nullable()->after('business_day_offset');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['fixed_due_date', 'business_day_offset', 'alert_offset_days']);
        });
    }
};
