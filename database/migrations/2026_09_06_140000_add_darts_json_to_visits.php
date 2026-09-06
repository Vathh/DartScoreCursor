<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_visits', function (Blueprint $table) {
            $table->json('darts')->nullable()->after('darts_in_visit');
        });

        Schema::table('quick_game_ffa_visits', function (Blueprint $table) {
            $table->json('darts')->nullable()->after('darts_in_visit');
        });
    }

    public function down(): void
    {
        Schema::table('game_visits', function (Blueprint $table) {
            $table->dropColumn('darts');
        });

        Schema::table('quick_game_ffa_visits', function (Blueprint $table) {
            $table->dropColumn('darts');
        });
    }
};
