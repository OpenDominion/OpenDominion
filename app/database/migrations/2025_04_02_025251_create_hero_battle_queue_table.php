<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeroBattleQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hero_battle_queue', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hero_id')->unsigned();
            $table->integer('level')->unsigned();
            $table->integer('rating')->unsigned();
            $table->timestamps();

            $table->foreign('hero_id')->references('id')->on('heroes');
        });

        Schema::table('heroes', function (Blueprint $table) {
            $table->integer('combat_rating')->default(1000)->after('experience');
        });

        Schema::table('hero_battles', function (Blueprint $table) {
            $table->boolean('pvp')->default(true)->after('current_turn');
        });

        Schema::table('hero_combatants', function (Blueprint $table) {
            $table->integer('hero_id')->unsigned()->nullable()->change();
            $table->integer('dominion_id')->unsigned()->nullable()->change();
            $table->integer('level')->unsigned()->default(0)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hero_battle_queue');

        Schema::table('heroes', function (Blueprint $table) {
            $table->dropColumn('combat_rating');
        });

        Schema::table('hero_battles', function (Blueprint $table) {
            $table->dropColumn('pvp');
        });

        Schema::table('hero_combatants', function (Blueprint $table) {
            $table->integer('hero_id')->unsigned()->change();
            $table->integer('dominion_id')->unsigned()->change();
            $table->dropColumn('level');
        });
    }
}
