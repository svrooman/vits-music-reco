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
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('spotify_playlist_id')->nullable();
            $table->string('spotify_playlist_uri')->nullable();
            $table->string('tidal_playlist_id')->nullable();
            $table->string('tidal_playlist_uri')->nullable();
            $table->string('apple_playlist_id')->nullable();
            $table->string('apple_playlist_uri')->nullable();
            $table->string('yogitunes_playlist_id')->nullable();
            $table->string('yogitunes_playlist_uri')->nullable();
            $table->json('tracks')->nullable();
            $table->boolean('is_public')->default(false);
            $table->json('meta')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
