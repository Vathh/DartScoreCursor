<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_leg_player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_leg_id')->constrained('game_legs')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->decimal('leg_average', 6, 2)->nullable();
            $table->decimal('first_nine_average', 6, 2)->nullable();
            $table->unsignedSmallInteger('highest_visit')->nullable();
            $table->unsignedSmallInteger('highest_finish')->nullable();
            $table->unsignedSmallInteger('darts_thrown')->nullable();
            $table->unsignedTinyInteger('checkout_dart')->nullable();
            $table->boolean('double_tracked')->default(false);
            $table->unsignedSmallInteger('double_attempts')->nullable();
            $table->unsignedSmallInteger('double_successes')->nullable();
            $table->timestamps();

            $table->unique(['game_leg_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_leg_player_stats');
    }
};
