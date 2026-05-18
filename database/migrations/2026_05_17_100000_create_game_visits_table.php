<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_leg_id')->constrained('game_legs')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedInteger('visit_number');
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('remaining_before');
            $table->unsignedSmallInteger('remaining_after');
            $table->unsignedTinyInteger('darts_in_visit')->default(3);
            $table->boolean('closed_leg')->default(false);
            $table->boolean('bust')->default(false);
            $table->boolean('is_voided')->default(false);
            $table->uuid('client_visit_id')->unique();
            $table->timestamps();

            $table->index(['game_leg_id', 'is_voided', 'visit_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_visits');
    }
};
