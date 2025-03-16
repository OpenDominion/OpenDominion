<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeroTournamentTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hero_tournaments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('round_id')->unsigned()->nullable();
            $table->string('name');
            $table->integer('current_round_number')->default(1);
            $table->boolean('finished')->default(false);
            $table->integer('winner_dominion_id')->unsigned()->nullable();
            $table->timestamps();
    
            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('winner_dominion_id')->references('id')->on('dominions');
        });

        Schema::create('hero_tournament_participants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hero_tournament_id')->unsigned();
            $table->integer('hero_id')->unsigned();
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('standing')->nullable();
            $table->boolean('eliminated')->default(false);
            $table->timestamps();
    
            $table->foreign('hero_tournament_id')->references('id')->on('hero_tournaments');
            $table->foreign('hero_id')->references('id')->on('heroes');
        });

        Schema::create('hero_tournament_battles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hero_tournament_id')->unsigned();
            $table->integer('hero_battle_id')->unsigned();
            $table->integer('round_number')->default(1);
    
            $table->foreign('hero_tournament_id')->references('id')->on('hero_tournaments');
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
        Schema::dropIfExists('hero_tournament_battles');
        Schema::dropIfExists('hero_tournament_participants');
        Schema::dropIfExists('hero_tournaments');
    }
}
