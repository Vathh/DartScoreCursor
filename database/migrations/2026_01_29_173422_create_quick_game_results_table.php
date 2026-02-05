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
        Schema::create('quick_game_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quick_game_id')->constrained('quick_games')->onDelete('cascade');
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            // Wynik gracza (liczba wygranych legów)
            $table->unsignedInteger('score')->default(0);
            // Miejsce gracza w meczu (1 = zwycięzca, 2 = drugi, itd.)
            $table->unsignedTinyInteger('place')->nullable();
            // Średnia punktowa w meczu (może być null jeśli nie obliczona)
            $table->decimal('average', 5, 2)->nullable();
            // Łączna liczba rzuconych lotek
            $table->unsignedInteger('darts_thrown')->nullable();
            // Łączna liczba zdobytych punktów
            $table->unsignedInteger('points_earned')->nullable();
            $table->timestamps();

            // Jeden gracz może mieć tylko jeden wynik w danym meczu
            $table->unique(['quick_game_id', 'player_id']);
            // Indeks dla szybkiego wyszukiwania
            $table->index(['quick_game_id', 'place']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_game_results');
    }
};
