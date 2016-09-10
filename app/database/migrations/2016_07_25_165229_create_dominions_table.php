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
            $table->increments('id');

            $table->integer('user_id')->unsigned();
            $table->integer('round_id')->unsigned();
            $table->integer('realm_id')->unsigned();
            $table->integer('race_id')->unsigned();

            $table->string('name');
            $table->integer('prestige');

            $table->integer('peasants');
            $table->integer('peasants_last_hour')->default(0);

            $table->integer('draft_rate');
            $table->integer('morale');

            $table->integer('resource_platinum');
            $table->integer('resource_food');
            $table->integer('resource_lumber');
            // todo: mana, ore, gems, tech, boats

            $table->integer('military_draftees');
            // todo: other units

            $table->integer('land_plain');
            $table->integer('land_forest');
            $table->integer('land_mountain');
            $table->integer('land_hill');
            $table->integer('land_swamp');
            $table->integer('land_water');
            $table->integer('land_cavern');

            $table->integer('building_home');
            $table->integer('building_alchemy');
            $table->integer('building_farm');
            $table->integer('building_lumber_yard');
            // todo: other buildings

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('realm_id')->references('id')->on('realms');
            $table->foreign('race_id')->references('id')->on('races');

            $table->unique(['user_id', 'round_id']);
            $table->unique(['round_id', 'name']);
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->foreign('monarch_dominion_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->dropForeign('realms_monarch_dominion_id_foreign');
        });

        Schema::drop('dominions');
    }
}
