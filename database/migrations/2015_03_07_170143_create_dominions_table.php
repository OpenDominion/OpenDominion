<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dominions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();

            $table->integer('user_id')->unsigned();
//            $table->integer('round_id')->unsigned();
//            $table->integer('realm_id')->unsigned();
//            $table->integer('race_id')->unsigned();

            // General
            $table->string('name');
            $table->integer('prestige')->unsigned();
//            $table->integer('networth_cache')->unsigned();

            // Population
            $table->integer('peasants')->unsigned();
            $table->integer('peasant_change_last_hour')->default(0);
            $table->integer('draftees')->unsigned();
            $table->integer('draft_rate')->unsigned();
            $table->integer('morale')->unsigned()->default(100);

            // Resources
            foreach (gamevar('types.resources') as $resource) {
                $table->integer('resource_' . $resource)->unsigned();
            }

            // Improvements
            // todo: improvements

            // Military
            foreach ([1, 2, 3, 4] as $i) {
                $table->integer('military_unit' . $i)->unsigned();
            }
            $table->integer('military_spies')->unsigned();
            $table->integer('military_wizards')->unsigned();
            $table->integer('military_archmages')->unsigned();
            $table->integer('wizard_strength')->unsigned()->default(100);

            // Land
            foreach (gamevar('types.land') as $land) {
                $table->integer('land_' . $land)->unsigned();
            }

            // Buildings
            foreach (gamevar('types.buildings') as $building) {
                $table->integer('building_' . $building)->unsigned();
            }

            // Bonuses
            $table->boolean('daily_land')->default(false);
            $table->boolean('daily_platinum')->default(false);

            // FKs
            $table->foreign('user_id')->references('id')->on('users');
//            $table->foreign('round_id')->references('id')->on('rounds');
//            $table->foreign('realm_id')->references('id')->on('realms');
//            $table->foreign('race_id')->references('id')->on('races');
        });

//        Schema::table('users', function (Blueprint $table) {
//            $table->foreign('active_dominion_id')->references('id')->on('dominions');
//        });

//        Schema::table('realms', function (Blueprint $table) {
//            $table->foreign('monarch_dominion_id')->references('id')->on('dominions');
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::table('realms', function (Blueprint $table) {
//            $table->dropForeign('realms_monarch_dominion_id_foreign');
//        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_active_dominion_id_foreign');
        });

        Schema::drop('dominions');
    }
}
