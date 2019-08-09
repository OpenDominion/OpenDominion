<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlayersPerRaceAndMixedAlignmentToRound extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('rounds', static function (Blueprint $table) {
            $table->unsignedInteger('players_per_race')
                ->default(0)
                ->after('pack_size');

            $table->boolean('mixed_alignment')
                ->default(false)
                ->after('players_per_race');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('rounds', static function (Blueprint $table) {
            $table->dropColumn(['players_per_race', 'mixed_alignment']);
        });
    }
}
