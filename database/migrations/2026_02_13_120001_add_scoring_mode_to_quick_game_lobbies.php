<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_game_lobbies', function (Blueprint $table) {
            $table->string('scoring_mode', 20)->default('each_own')->after('game_type'); // one_device | each_own
        });
    }

    public function down(): void
    {
        Schema::table('quick_game_lobbies', function (Blueprint $table) {
            $table->dropColumn('scoring_mode');
        });
    }
};
