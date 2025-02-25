<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeroBattleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hero_battles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('round_id')->unsigned()->nullable();
            $table->integer('current_turn')->default(1);
            $table->integer('winner_combatant_id')->unsigned()->nullable();
            $table->boolean('finished')->default(false);
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
        });

        Schema::create('hero_combatants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hero_battle_id')->unsigned();
            $table->integer('hero_id')->unsigned();
            $table->integer('dominion_id')->unsigned();
            $table->string('name');
            $table->integer('health');
            $table->integer('attack');
            $table->integer('defense');
            $table->integer('evasion');
            $table->integer('focus');
            $table->integer('counter');
            $table->integer('recover');
            $table->integer('current_health');
            $table->boolean('has_focus')->default(false);
            $table->string('current_action')->nullable();
            $table->string('last_action')->nullable();
            $table->text('actions')->nullable();
            $table->boolean('automated')->nullable();
            $table->string('strategy')->nullable();
            $table->timestamps();

            $table->foreign('hero_id')->references('id')->on('heroes');
            $table->foreign('dominion_id')->references('id')->on('dominions');
        });

        Schema::create('hero_battle_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hero_battle_id')->unsigned();
            $table->integer('combatant_id')->unsigned();
            $table->integer('target_combatant_id')->unsigned()->nullable();
            $table->integer('turn');
            $table->string('action');
            $table->integer('damage');
            $table->integer('health');
            $table->string('description');
            $table->timestamps();

            $table->foreign('hero_battle_id')->references('id')->on('hero_battles');
            $table->foreign('combatant_id')->references('id')->on('hero_combatants');
            $table->foreign('target_combatant_id')->references('id')->on('hero_combatants');
        });

        Schema::table('hero_battles', function (Blueprint $table) {
            $table->foreign('winner_combatant_id')->references('id')->on('hero_combatants');
        });

        Schema::table('hero_combatants', function (Blueprint $table) {
            $table->foreign('hero_battle_id')->references('id')->on('hero_battles');
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
            $table->dropForeign('hero_battles_winner_combatant_id_foreign');
        });

        Schema::table('hero_combatants', function (Blueprint $table) {
            $table->dropForeign('hero_combatants_hero_battle_id_foreign');
        });

        Schema::dropIfExists('hero_battle_actions');
        Schema::dropIfExists('hero_combatants');
        Schema::dropIfExists('hero_battles');
    }
}
