<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consent / lawful-basis records, including cross-border-transfer basis (SCCs)
 * where data leaves the UAE (SPEC.md §6/§8/§10). Captured at onboarding;
 * fully wired in M10. Tenant-owned.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose');
            $table->timestamp('granted_at')->nullable();
            $table->string('transfer_basis')->nullable(); // e.g. SCCs
            $table->timestamps();

            $table->index(['tenant_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
