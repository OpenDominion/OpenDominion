<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpendingStatsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_total_platinum_spent_construction')->after('stat_total_boat_production')->default(0);
            $table->unsignedInteger('stat_total_lumber_spent_construction')->after('stat_total_platinum_spent_construction')->default(0);
            $table->unsignedInteger('stat_total_platinum_spent_exploration')->after('stat_total_lumber_spent_construction')->default(0);
            $table->unsignedInteger('stat_total_platinum_spent_investment')->after('stat_total_platinum_spent_exploration')->default(0);
            $table->unsignedInteger('stat_total_lumber_spent_investment')->after('stat_total_platinum_spent_investment')->default(0);
            $table->unsignedInteger('stat_total_mana_spent_investment')->after('stat_total_lumber_spent_investment')->default(0);
            $table->unsignedInteger('stat_total_ore_spent_investment')->after('stat_total_mana_spent_investment')->default(0);
            $table->unsignedInteger('stat_total_gems_spent_investment')->after('stat_total_ore_spent_investment')->default(0);
            $table->unsignedInteger('stat_total_platinum_spent_rezoning')->after('stat_total_gems_spent_investment')->default(0);
            $table->unsignedInteger('stat_total_platinum_spent_training')->after('stat_total_platinum_spent_rezoning')->default(0);
            $table->unsignedInteger('stat_total_lumber_spent_training')->after('stat_total_platinum_spent_training')->default(0);
            $table->unsignedInteger('stat_total_mana_spent_training')->after('stat_total_lumber_spent_training')->default(0);
            $table->unsignedInteger('stat_total_ore_spent_training')->after('stat_total_mana_spent_training')->default(0);
            $table->unsignedInteger('stat_total_gems_spent_training')->after('stat_total_ore_spent_training')->default(0);
            $table->unsignedInteger('stat_total_food_decay')->after('stat_total_gems_spent_training')->default(0);
            $table->unsignedInteger('stat_total_lumber_decay')->after('stat_total_food_decay')->default(0);
            $table->unsignedInteger('stat_total_mana_decay')->after('stat_total_lumber_decay')->default(0);
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->integer('resource_food_decay')->after('resource_food')->default(0);
            $table->integer('resource_lumber_decay')->after('resource_lumber')->default(0);
            $table->integer('resource_mana_decay')->after('resource_mana')->default(0);
            $table->float('resource_boat_production')->after('resource_boats')->default(0);
            $table->text('expiring_spells')->after('starvation_casualties')->nullable();
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
                'stat_total_platinum_spent_construction',
                'stat_total_lumber_spent_construction',
                'stat_total_platinum_spent_exploration',
                'stat_total_platinum_spent_investment',
                'stat_total_lumber_spent_investment',
                'stat_total_mana_spent_investment',
                'stat_total_ore_spent_investment',
                'stat_total_gems_spent_investment',
                'stat_total_platinum_spent_rezoning',
                'stat_total_platinum_spent_training',
                'stat_total_lumber_spent_training',
                'stat_total_mana_spent_training',
                'stat_total_ore_spent_training',
                'stat_total_gems_spent_training',
                'stat_total_food_decay',
                'stat_total_lumber_decay',
                'stat_total_mana_decay'
            ]);
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn([
                'resource_food_decay',
                'resource_lumber_decay',
                'resource_mana_decay',
                'resource_boat_production',
                'expiring_spells'
            ]);
        });
    }
}
