<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('playback_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained('screens')->cascadeOnDelete();
            $table->foreignId('ad_id')->nullable()->constrained('ads')->nullOnDelete();
            $table->timestamp('played_at');
            $table->unsignedInteger('duration')->default(0);
            $table->json('extra')->nullable(); // ex. app_version, battery, storage_free
            $table->timestamps();

            $table->index(['screen_id', 'played_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playback_logs');
    }
};
