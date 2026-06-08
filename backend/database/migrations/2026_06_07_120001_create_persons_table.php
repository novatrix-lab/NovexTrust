<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Individuals whose documents are tracked (SPEC.md §6): employees, owners,
 * dependents. Optionally linked to an entity. Tenant-owned.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->string('full_name');
            $table->string('role'); // App\Enums\PersonRole
            $table->string('passport_no')->nullable();
            $table->string('emirates_id_no')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
