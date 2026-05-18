<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_games', function (Blueprint $table) {
            if (! Schema::hasColumn('quick_games', 'legs_count')) {
                $table->unsignedTinyInteger('legs_count')->default(3)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quick_games', function (Blueprint $table) {
            if (Schema::hasColumn('quick_games', 'legs_count')) {
                $table->dropColumn('legs_count');
            }
        });
    }
};
