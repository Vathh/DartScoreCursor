<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_game_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('quick_game_sessions', 'quick_game_id')) {
                $table->foreignId('quick_game_id')
                    ->nullable()
                    ->after('lobby_id')
                    ->constrained('quick_games')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quick_game_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('quick_game_sessions', 'quick_game_id')) {
                $table->dropConstrainedForeignId('quick_game_id');
            }
        });
    }
};
