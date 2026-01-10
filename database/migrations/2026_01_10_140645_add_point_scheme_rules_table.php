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
        Schema::create('point_scheme_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('point_scheme_id');

            $table->string('elimination_stage');
            $table->unsignedSmallInteger('place')->nullable();
            $table->unsignedSmallInteger('points');
            $table->timestamps();

            $table->unique(['point_scheme_id', 'elimination_stage', 'place'], 'unique_point_scheme_rules');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_scheme_rules');
    }
};
