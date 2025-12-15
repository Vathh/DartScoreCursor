<?php /** @noinspection SqlNoDataSourceInspection */

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
        Schema::table('group_standings', function (Blueprint $table) {
            $table->dropColumn('legs_difference');
        });

        Schema::table('group_standings', function (Blueprint $table) {
            $table->integer('legs_won')->default(0)->change();
            $table->integer('legs_lost')->default(0)->change();
        });

        DB::statement("
            ALTER TABLE group_standings
            ADD legs_difference INT
            AS (legs_won - legs_lost)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_standings', function (Blueprint $table) {
            $table->dropColumn('legs_difference');
        });

        Schema::table('group_standings', function (Blueprint $table) {
            $table->unsignedInteger('legs_won')->default(0)->change();
            $table->unsignedInteger('legs_lost')->default(0)->change();
        });

        DB::statement("
            ALTER TABLE group_standings
            ADD legs_difference INT UNSIGNED
            AS (legs_won - legs_lost)
        ");
    }
};
