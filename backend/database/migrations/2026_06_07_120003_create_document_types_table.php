<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document types — the heart of the rules engine (SPEC.md §6/§7). Seeded by a
 * rule pack (M4), GLOBAL (not tenant-owned).
 *
 * Essentials-plus additions (SPEC field lists are non-exhaustive): rule_pack_id
 * (versioning), level (entity vs person), jurisdiction (mainland/freezone/any).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_pack_id')->constrained()->cascadeOnDelete();
            $table->string('country', 2)->default('AE');
            $table->string('code');
            $table->string('name');
            $table->string('category');               // App\Enums\DocumentCategory
            $table->string('level');                  // App\Enums\DocumentLevel
            $table->string('jurisdiction')->nullable(); // null = any (App\Enums\JurisdictionType)
            $table->string('default_renewal_cycle');  // App\Enums\RenewalCycle
            $table->unsignedSmallInteger('default_lead_days')->default(30);
            $table->text('consequence_note')->nullable();
            $table->timestamps();

            $table->unique(['rule_pack_id', 'code', 'jurisdiction']);
            $table->index(['country', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
