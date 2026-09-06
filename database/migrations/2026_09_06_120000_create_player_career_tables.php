<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->uuid('client_uuid');
            $table->string('game_type', 32);
            $table->timestamp('completed_at');
            $table->json('format')->nullable();
            $table->json('metrics');
            $table->timestamps();

            $table->unique(['player_id', 'client_uuid']);
            $table->index(['player_id', 'completed_at']);
        });

        Schema::create('player_game_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('source', 16);
            $table->string('game_type', 32);
            $table->timestamp('occurred_at');
            $table->uuid('client_uuid')->nullable();
            $table->nullableMorphs('sourceable');
            $table->json('metrics');
            $table->timestamps();

            $table->index(['player_id', 'client_uuid']);
            $table->unique(
                ['player_id', 'sourceable_type', 'sourceable_id'],
                'player_game_snapshots_sourceable_unique',
            );
            $table->index(['player_id', 'occurred_at', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_game_snapshots');
        Schema::dropIfExists('training_games');
    }
};
