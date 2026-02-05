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
        Schema::table('quick_games', function (Blueprint $table) {
            $table->foreignId('lobby_id')->nullable()->after('id')->constrained('quick_game_lobbies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_games', function (Blueprint $table) {
            $table->dropForeign(['lobby_id']);
            $table->dropColumn('lobby_id');
        });
    }
};
