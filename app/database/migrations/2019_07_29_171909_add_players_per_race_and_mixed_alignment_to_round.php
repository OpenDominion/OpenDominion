<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlayersPerRaceAndMixedAlignmentToRound extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedInteger('players_per_race')->default(0)->after('pack_size');
            $table->boolean('mixed_alignment')->default(false)->after('players_per_race');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn('players_per_race');
            $table->dropColumn('mixed_alignment');
        });
    }
}
