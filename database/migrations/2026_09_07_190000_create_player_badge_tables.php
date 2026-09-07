<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_badge_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('badge_key', 16);
            $table->unsignedInteger('amount');
            $table->string('source_kind', 16);
            $table->unsignedBigInteger('source_id');
            $table->timestamps();

            $table->unique(
                ['player_id', 'category', 'badge_key', 'source_kind', 'source_id'],
                'player_badge_events_unique_source',
            );
            $table->index(['source_kind', 'source_id']);
        });

        Schema::create('player_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('badge_key', 16);
            $table->unsignedInteger('times_earned')->default(0);
            $table->timestamp('first_unlocked_at')->nullable();
            $table->timestamp('last_earned_at')->nullable();
            $table->string('source_quality', 16)->default('manual');
            $table->timestamps();

            $table->unique(['player_id', 'category', 'badge_key']);
            $table->index(['player_id', 'category']);
        });

        Schema::create('badge_game_commits', function (Blueprint $table) {
            $table->id();
            $table->string('source_kind', 16);
            $table->unsignedBigInteger('source_id');
            $table->timestamps();

            $table->unique(['source_kind', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badge_game_commits');
        Schema::dropIfExists('player_badges');
        Schema::dropIfExists('player_badge_events');
    }
};
