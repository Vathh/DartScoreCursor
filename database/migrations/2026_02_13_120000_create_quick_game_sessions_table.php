<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained('quick_game_lobbies')->onDelete('cascade');
            $table->unsignedBigInteger('host_user_id');
            $table->string('scoring_mode', 20)->default('each_own'); // one_device | each_own
            $table->json('state');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_game_sessions');
    }
};
