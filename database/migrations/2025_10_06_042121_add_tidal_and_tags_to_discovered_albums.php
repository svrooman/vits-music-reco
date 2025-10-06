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
        Schema::table('discovered_albums', function (Blueprint $table) {
            $table->boolean('tidal_added')->default(false)->after('description');
            $table->timestamp('tidal_added_at')->nullable()->after('tidal_added');
            $table->json('tags')->nullable()->after('tidal_added_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discovered_albums', function (Blueprint $table) {
            $table->dropColumn(['tidal_added', 'tidal_added_at', 'tags']);
        });
    }
};
