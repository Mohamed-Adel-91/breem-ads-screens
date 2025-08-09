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
        Schema::create('screen_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained('screens')->cascadeOnDelete();
            $table->string('current_ad_code')->nullable(); // last ad code at the time of the log
            $table->enum('status', ['online', 'offline'])->default('online');
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamps();

            $table->index(['screen_id', 'reported_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screen_logs');
    }
};
