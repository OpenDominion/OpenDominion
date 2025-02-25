<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCombatStatsToHeroesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->integer('stat_combat_wins')->unsigned()->default(0)->after('experience');
            $table->integer('stat_combat_losses')->unsigned()->default(0)->after('stat_combat_wins');
            $table->integer('stat_combat_draws')->unsigned()->default(0)->after('stat_combat_losses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->dropColumn('stat_combat_wins');
            $table->dropColumn('stat_combat_losses');
            $table->dropColumn('stat_combat_draws');
        });
    }
}
