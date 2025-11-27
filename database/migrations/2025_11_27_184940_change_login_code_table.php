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
        Schema::table('login_codes', function (Blueprint $table) {
           $table->foreignId('tournament_id')->constrained('tournaments')->onDelete('cascade');
           $table->timestamp('expires_at')->nullable();
           $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_codes', function (Blueprint $table) {
            $table->dropForeign('login_codes_tournament_id_foreign');
            $table->dropColumn('tournament_id');
            $table->dropColumn('expires_at');
        });
    }
};
