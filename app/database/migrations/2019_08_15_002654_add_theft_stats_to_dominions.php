<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTheftStatsToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->unsignedInteger('stat_total_platinum_stolen')->after('stat_total_land_conquered')->default(0);
            $table->unsignedInteger('stat_total_food_stolen')->after('stat_total_platinum_stolen')->default(0);
            $table->unsignedInteger('stat_total_lumber_stolen')->after('stat_total_food_stolen')->default(0);
            $table->unsignedInteger('stat_total_mana_stolen')->after('stat_total_lumber_stolen')->default(0);
            $table->unsignedInteger('stat_total_ore_stolen')->after('stat_total_mana_stolen')->default(0);
            $table->unsignedInteger('stat_total_gems_stolen')->after('stat_total_ore_stolen')->default(0);
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
                'stat_total_platinum_stolen',
                'stat_total_food_stolen',
                'stat_total_lumber_stolen',
                'stat_total_mana_stolen',
                'stat_total_ore_stolen',
                'stat_total_gems_stolen',
            ]);
        });
    }
}
