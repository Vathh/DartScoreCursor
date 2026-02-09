<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_game_lobbies', function (Blueprint $table) {
            if (!Schema::hasColumn('quick_game_lobbies', 'game_type')) {
                $table->string('game_type', 20)->default('501')->after('legs_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quick_game_lobbies', function (Blueprint $table) {
            if (Schema::hasColumn('quick_game_lobbies', 'game_type')) {
                $table->dropColumn('game_type');
            }
        });
    }
};
