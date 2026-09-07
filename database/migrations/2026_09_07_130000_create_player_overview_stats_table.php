<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_overview_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->unique()->constrained('players')->cascadeOnDelete();
            $table->unsignedInteger('games_total')->default(0);
            $table->unsignedInteger('wins_total')->default(0);
            $table->unsignedInteger('games_tournament')->default(0);
            $table->unsignedInteger('wins_tournament')->default(0);
            $table->unsignedInteger('games_league')->default(0);
            $table->unsignedInteger('wins_league')->default(0);
            $table->unsignedInteger('games_quick')->default(0);
            $table->unsignedInteger('wins_quick')->default(0);
            $table->unsignedInteger('tournament_place_1')->default(0);
            $table->unsignedInteger('tournament_place_2')->default(0);
            $table->unsignedInteger('tournament_place_3')->default(0);
            $table->unsignedInteger('league_titles')->default(0);
            $table->unsignedInteger('unique_opponents')->default(0);
            $table->unsignedInteger('activity_days')->default(0);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_activity_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_overview_stats');
    }
};
