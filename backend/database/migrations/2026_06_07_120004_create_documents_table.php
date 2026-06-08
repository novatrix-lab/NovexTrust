<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stored documents (SPEC.md §6). Attached to an entity and/or a person, of a
 * given document type. Tenant-owned.
 *
 * `file_ref` is the opaque vault key (the app DB never stores file bytes) —
 * populated by the encrypted vault in M5. `extracted` and `confirmed` are
 * SEPARATE flags: a misread date must never silently become a live deadline
 * (SPEC.md §8, M6 human-confirm step).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('pending'); // App\Enums\DocumentStatus
            $table->string('file_ref')->nullable();        // opaque vault key (M5)
            $table->boolean('extracted')->default(false);
            $table->boolean('confirmed')->default(false);
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'document_type_id']);
            $table->index(['tenant_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
