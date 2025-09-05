<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->string('page')->unique(); // e.g., 'front.homepage', 'front.contact-us'
            $table->json('title')->nullable(); // Translatable: title for <title> tag.
            $table->json('description')->nullable(); // Translatable meta description.
            $table->json('keywords')->nullable(); // Translatable meta keywords.
            $table->json('og_title')->nullable(); // Translatable Open Graph title.
            $table->json('og_description')->nullable(); // Translatable Open Graph description.
            $table->string('canonical')->nullable(); // Optional canonical URL.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seo_metas');
    }
};
