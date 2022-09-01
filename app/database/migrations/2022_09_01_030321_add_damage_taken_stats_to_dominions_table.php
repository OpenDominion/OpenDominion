<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDamageTakenStatsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_assassinate_draftees_damage_received')->after('stat_assassinate_draftees_damage')->default(0);
            $table->unsignedInteger('stat_assassinate_wizards_damage_received')->after('stat_assassinate_wizards_damage')->default(0);
            $table->unsignedInteger('stat_magic_snare_damage_received')->after('stat_magic_snare_damage')->default(0);
            $table->unsignedInteger('stat_sabotage_boats_damage_received')->after('stat_sabotage_boats_damage')->default(0);
            $table->unsignedInteger('stat_disband_spies_damage_received')->after('stat_disband_spies_damage')->default(0);
            $table->unsignedInteger('stat_fireball_damage_received')->after('stat_fireball_damage')->default(0);
            $table->unsignedInteger('stat_lightning_bolt_damage_received')->after('stat_lightning_bolt_damage')->default(0);
            $table->unsignedInteger('stat_earthquake_hours_received')->after('stat_earthquake_hours')->default(0);
            $table->unsignedInteger('stat_great_flood_hours_received')->after('stat_great_flood_hours')->default(0);
            $table->unsignedInteger('stat_insect_swarm_hours_received')->after('stat_insect_swarm_hours')->default(0);
            $table->unsignedInteger('stat_plague_hours_received')->after('stat_plague_hours')->default(0);
            $table->unsignedInteger('stat_spells_deflected')->after('stat_spells_reflected')->default(0);
        });

        $dominionStats = array();
        $statisticsToPopulate = [
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
            'stat_spells_reflected',
        ];

        foreach ($statisticsToPopulate as $statisticName) {
            $newStat = "{$statisticName}_received";
            if ($statisticName == "stat_spells_reflected") {
                $newStat = "stat_spells_deflected";
            }
            $eventHistory = DB::table('dominion_history')->where('delta', 'like', "%{$statisticName}%")->get();
            foreach ($eventHistory as $event) {
                $data = json_decode($event->delta);
                if (isset($data->{$statisticName}) && isset($data->target_dominion_id)) {
                    if (!isset($dominionStats[$data->target_dominion_id][$newStat])) {
                        $dominionStats[$data->target_dominion_id][$newStat] = 0;
                    }
                    $dominionStats[$data->target_dominion_id][$newStat] += $data->{$statisticName};
                }
            }
        }

        foreach ($dominionStats as $id => $stats) {
            DB::table('dominions')->where('id', $id)->update($stats);
        }
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
                'stat_assassinate_draftees_damage_received',
                'stat_assassinate_wizards_damage_received',
                'stat_magic_snare_damage_received',
                'stat_sabotage_boats_damage_received',
                'stat_disband_spies_damage_received',
                'stat_fireball_damage_received',
                'stat_lightning_bolt_damage_received',
                'stat_earthquake_hours_received',
                'stat_great_flood_hours_received',
                'stat_insect_swarm_hours_received',
                'stat_plague_hours_received',
                'stat_spells_deflected',
            ]);
        });
    }
}
