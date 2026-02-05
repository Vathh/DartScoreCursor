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
        Schema::create('quick_game_lobbies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade'); // Twórca lobby
            $table->string('code', 8)->unique(); // Unikalny kod lobby do dołączania
            $table->enum('status', ['waiting', 'starting', 'in_progress', 'finished'])->default('waiting');
            $table->timestamp('started_at')->nullable();
            $table->timestamps();

            // Indeks dla szybkiego wyszukiwania po kodzie
            $table->index('code');
            $table->index(['host_id', 'status']);
        });

        // Tabela pivot dla graczy w lobby
        Schema::create('quick_game_lobby_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained('quick_game_lobbies')->onDelete('cascade');
            $table->foreignId('player_id')->nullable()->constrained('players')->onDelete('cascade');
            // Dla graczy tymczasowych - tylko nazwa (nie zapisywana w bazie, ale potrzebna w lobby)
            $table->string('temp_player_name')->nullable();
            $table->boolean('is_registered')->default(true);
            $table->boolean('is_ready')->default(false); // Czy gracz jest gotowy do startu
            $table->timestamps();

            // Indeksy - unique nie zadziała z null, więc sprawdzamy w logice aplikacji
            $table->index(['lobby_id', 'player_id']);
            $table->index(['lobby_id', 'is_ready']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_game_lobby_players');
        Schema::dropIfExists('quick_game_lobbies');
    }
};
