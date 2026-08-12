<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a real activation flag to section_items.
 *
 * Before this migration the admin toggle wrote `is_active` into the translated
 * `data` JSON, which the public renderer never read — so no item could ever be
 * hidden. The column defaults to true so every existing row keeps rendering
 * exactly as it does today.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('section_items', 'is_active')) {
            return;
        }

        Schema::table('section_items', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('order');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('section_items', 'is_active')) {
            return;
        }

        Schema::table('section_items', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
