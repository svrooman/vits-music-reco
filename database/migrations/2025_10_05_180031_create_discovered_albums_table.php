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
        Schema::create('discovered_albums', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // dandelion, bandcamp, etc
            $table->string('artist');
            $table->string('album');
            $table->string('url')->nullable();
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('discovered_at')->useCurrent();
            $table->timestamps();

            $table->index(['source', 'discovered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discovered_albums');
    }
};
