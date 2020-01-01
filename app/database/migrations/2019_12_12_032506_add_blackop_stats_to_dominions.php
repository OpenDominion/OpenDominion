<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBlackopStatsToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_spy_prestige')->after('stat_total_gems_stolen')->default(0);
            $table->unsignedInteger('stat_wizard_prestige')->after('stat_spy_prestige')->default(0);
            $table->unsignedInteger('stat_assassinate_draftees_damage')->after('stat_wizard_prestige')->default(0);
            $table->unsignedInteger('stat_assassinate_wizards_damage')->after('stat_assassinate_draftees_damage')->default(0);
            $table->unsignedInteger('stat_magic_snare_damage')->after('stat_assassinate_wizards_damage')->default(0);
            $table->unsignedInteger('stat_sabotage_boats_damage')->after('stat_magic_snare_damage')->default(0);
            $table->unsignedInteger('stat_disband_spies_damage')->after('stat_sabotage_boats_damage')->default(0);
            $table->unsignedInteger('stat_fireball_damage')->after('stat_disband_spies_damage')->default(0);
            $table->unsignedInteger('stat_lightning_bolt_damage')->after('stat_fireball_damage')->default(0);
            $table->unsignedInteger('stat_earthquake_hours')->after('stat_lightning_bolt_damage')->default(0);
            $table->unsignedInteger('stat_great_flood_hours')->after('stat_earthquake_hours')->default(0);
            $table->unsignedInteger('stat_insect_swarm_hours')->after('stat_great_flood_hours')->default(0);
            $table->unsignedInteger('stat_plague_hours')->after('stat_insect_swarm_hours')->default(0);
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
            $table->dropColumn([
                'stat_spy_prestige',
                'stat_wizard_prestige',
                'stat_assassinate_draftees_damage',
                'stat_assassinate_wizards_damage',
                'stat_magic_snare_damage',
                'stat_sabotage_boats_damage',
                'stat_disband_spies_damage',
                'stat_fireball_damage',
                'stat_lightning_bolt_damage',
                'stat_earthquake_hours',
                'stat_great_flood_hours',
                'stat_insect_swarm_hours',
                'stat_plague_hours',
            ]);
        });
    }
}
