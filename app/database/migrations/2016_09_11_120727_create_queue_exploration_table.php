<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueExplorationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue_exploration', function (Blueprint $table) {
            $table->integer('dominion_id')->unsigned();
            $table->string('land_type');
            $table->integer('amount')->unsigned();
            $table->integer('hours')->unsigned();
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'land_type', 'hours']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('queue_exploration');
    }
}
