<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_game_lobby_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained('quick_game_lobbies')->onDelete('cascade');
            $table->foreignId('invited_player_id')->constrained('players')->onDelete('cascade');
            $table->string('status', 20)->default('pending'); // pending, accepted, rejected
            $table->timestamps();
            $table->unique(['lobby_id', 'invited_player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_game_lobby_invitations');
    }
};
