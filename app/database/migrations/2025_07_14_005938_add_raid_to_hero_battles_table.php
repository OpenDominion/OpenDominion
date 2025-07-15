<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRaidToHeroBattlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hero_battles', function (Blueprint $table) {
            $table->unsignedInteger('raid_tactic_id')->nullable()->after('pvp');

            $table->foreign('raid_tactic_id')->references('id')->on('raid_objective_tactics');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hero_battles', function (Blueprint $table) {
            $table->dropForeign('hero_battles_raid_tactic_id_foreign');

            $table->dropColumn('raid_tactic_id');
        });
    }
}
