<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business entities (SPEC.md §6): a "client" for an agency tenant, a "business"
 * for an SME tenant. Tenant-owned (strict isolation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('jurisdiction_type')->nullable(); // App\Enums\JurisdictionType
            $table->string('authority')->nullable();         // e.g. DED, DMCC, JAFZA
            $table->string('license_number')->nullable();
            $table->string('country', 2)->default('AE');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'legal_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
