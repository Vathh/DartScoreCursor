<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_overview_stats', function (Blueprint $table) {
            $table->json('top_opponents')->nullable()->after('unique_opponents');
        });
    }

    public function down(): void
    {
        Schema::table('player_overview_stats', function (Blueprint $table) {
            $table->dropColumn('top_opponents');
        });
    }
};
