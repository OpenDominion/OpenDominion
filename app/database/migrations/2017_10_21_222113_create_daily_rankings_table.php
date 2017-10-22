<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyRankingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_rankings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('round_id');
            $table->unsignedInteger('dominion_id');
            $table->string('dominion_name');
            $table->string('race_name');
            $table->unsignedInteger('realm_number');
            $table->string('realm_name');
            $table->unsignedInteger('land');
            $table->unsignedInteger('land_rank')->nullable();
            $table->integer('land_rank_change')->nullable();
            $table->unsignedInteger('networth');
            $table->unsignedInteger('networth_rank')->nullable();
            $table->integer('networth_rank_change')->nullable();
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('dominion_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_rankings');
    }
}
