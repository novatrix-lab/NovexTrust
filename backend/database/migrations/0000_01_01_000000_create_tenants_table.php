<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenants are the top of the isolation boundary (SPEC.md §6). Named to sort
 * before the users migration so users.tenant_id can reference it.
 *
 * `type` maps to App\Enums\TenantType (agency|sme); `country` defaults to AE
 * (UAE-first, GCC-ready); `status` is a plain string for now (active|suspended).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('country', 2)->default('AE');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
