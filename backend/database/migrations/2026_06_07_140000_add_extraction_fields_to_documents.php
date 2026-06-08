<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extraction fields (M6):
 *  - extracted_data    — raw structured suggestions from the extractor (shown to
 *                        the human at the confirm step; never authoritative)
 *  - mime_type         — content type, passed to the extractor
 *  - original_filename — for display/download naming
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->json('extracted_data')->nullable()->after('confirmed_at');
            $table->string('mime_type')->nullable()->after('file_ref');
            $table->string('original_filename')->nullable()->after('mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['extracted_data', 'mime_type', 'original_filename']);
        });
    }
};
