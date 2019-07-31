<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDominionStatisticsFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->unsignedInteger('stat_attacking_success')->after('building_dock')->default(0);
            $table->unsignedInteger('stat_defending_success')->after('stat_attacking_success')->default(0);
            $table->unsignedInteger('stat_espionage_success')->after('stat_defending_success')->default(0);
            $table->unsignedInteger('stat_spell_success')->after('stat_espionage_success')->default(0);
            $table->unsignedInteger('stat_total_platinum_production')->after('stat_spell_success')->default(0);
            $table->unsignedInteger('stat_total_food_production')->after('stat_total_platinum_production')->default(0);
            $table->unsignedInteger('stat_total_lumber_production')->after('stat_total_food_production')->default(0);
            $table->unsignedInteger('stat_total_mana_production')->after('stat_total_lumber_production')->default(0);
            $table->unsignedInteger('stat_total_ore_production')->after('stat_total_mana_production')->default(0);
            $table->unsignedInteger('stat_total_gem_production')->after('stat_total_ore_production')->default(0);
            $table->unsignedInteger('stat_total_tech_production')->after('stat_total_gem_production')->default(0);
            $table->float('stat_total_boat_production')->after('stat_total_tech_production')->default(0);
            $table->unsignedInteger('stat_total_land_explored')->after('stat_total_boat_production')->default(0);
            $table->unsignedInteger('stat_total_land_conquered')->after('stat_total_land_explored')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->dropColumn([
                'stat_attacking_success',
                'stat_defending_success',
                'stat_espionage_success',
                'stat_spell_success',
                'stat_total_platinum_production',
                'stat_total_food_production',
                'stat_total_lumber_production',
                'stat_total_mana_production',
                'stat_total_ore_production',
                'stat_total_gem_production',
                'stat_total_tech_production',
                'stat_total_boat_production',
                'stat_total_land_explored',
                'stat_total_land_conquered',
            ]);
        });
    }
}
