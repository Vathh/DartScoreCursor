<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_game_lobbies', function (Blueprint $table) {
            if (! Schema::hasColumn('quick_game_lobbies', 'quick_game_id')) {
                $table->foreignId('quick_game_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('quick_games')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('quick_game_lobbies', 'player_order')) {
                $table->json('player_order')->nullable()->after('quick_game_id');
            }
        });

        Schema::dropIfExists('quick_game_sessions');
    }

    public function down(): void
    {
        Schema::table('quick_game_lobbies', function (Blueprint $table) {
            if (Schema::hasColumn('quick_game_lobbies', 'quick_game_id')) {
                $table->dropConstrainedForeignId('quick_game_id');
            }
            if (Schema::hasColumn('quick_game_lobbies', 'player_order')) {
                $table->dropColumn('player_order');
            }
        });

        Schema::create('quick_game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained('quick_game_lobbies')->onDelete('cascade');
            $table->foreignId('quick_game_id')->nullable()->constrained('quick_games')->nullOnDelete();
            $table->foreignId('host_user_id')->constrained('users')->onDelete('cascade');
            $table->string('scoring_mode', 20)->default('each_own');
            $table->json('state');
            $table->timestamps();
        });
    }
};
