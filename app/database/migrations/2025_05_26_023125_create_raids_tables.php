<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRaidsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raids', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('round_id')->unsigned();
            $table->string('name');
            $table->string('description');
            $table->string('reward_resource');
            $table->integer('reward_amount');
            $table->string('completion_reward_resource');
            $table->integer('completion_reward_amount');
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
        });

        Schema::create('raid_objectives', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('raid_id')->unsigned();
            $table->string('name');
            $table->string('description');
            $table->integer('order')->unsigned();
            $table->integer('score_required')->unsigned();
            $table->timestamps();

            $table->foreign('raid_id')->references('id')->on('raids');
        });

        Schema::create('raid_objective_tactics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('raid_objective_id')->unsigned();
            $table->string('type');
            $table->string('name');
            $table->float('modifier');
            $table->text('attributes')->nullable();
            $table->text('bonuses')->nullable();
            $table->timestamps();

            $table->foreign('raid_objective_id')->references('id')->on('raid_objectives');
        });

        Schema::create('raid_contributions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('realm_id')->unsigned();
            $table->integer('dominion_id')->unsigned();
            $table->integer('raid_objective_id')->unsigned();
            $table->string('type');
            $table->integer('score')->unsigned();
            $table->timestamps();

            $table->foreign('realm_id')->references('id')->on('realms');
            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('raid_objective_id')->references('id')->on('raid_objectives');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('raid_contributions');
        Schema::dropIfExists('raid_objective_tactics');
        Schema::dropIfExists('raid_objectives');
        Schema::dropIfExists('raids');
    }
}
