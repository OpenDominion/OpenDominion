<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOverpopulationCasualtiesToDominionTickTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->text('overpopulation_casualties')->after('starvation_casualties')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn('overpopulation_casualties');
        });
    }
}
