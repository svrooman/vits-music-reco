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
        Schema::create('scrape_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Dandelion Records", "Constellation Records"
            $table->string('type'); // bandcamp, shopify, custom
            $table->string('url');
            $table->boolean('active')->default(true);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrape_sources');
    }
};
