<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scheduled alerts for a deadline (SPEC.md §6/§8) on the 90/60/30/7/1-day
 * cadence. Queued and sent in M8. tenant_id denormalised for direct scoping.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deadline_id')->constrained()->cascadeOnDelete();
            $table->string('channel');                       // App\Enums\AlertChannel
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');    // App\Enums\AlertStatus
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['tenant_id', 'deadline_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
