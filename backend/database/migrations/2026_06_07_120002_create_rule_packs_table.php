<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Country rule packs (SPEC.md §6/§7). A rule pack is a versioned set of document
 * types + deadline logic for a country. GLOBAL (not tenant-owned) — shared by all
 * tenants. Only the UAE pack ships active in Phase 1; versioning makes rule
 * changes auditable (CLAUDE.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_packs', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2);
            $table->string('version');
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->unique(['country', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_packs');
    }
};
