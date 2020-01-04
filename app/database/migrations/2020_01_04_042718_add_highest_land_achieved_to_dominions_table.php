<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHighestLandAchievedToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->integer('highest_land_achieved')->after('stat_plague_hours')->unsigned()->default(250);
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->integer('highest_land_achieved')->after('starvation_casualties')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('highest_land_achieved');
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn('highest_land_achieved');
        });
    }
}
