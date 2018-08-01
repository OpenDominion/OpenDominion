<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueLandIncomingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue_land_incoming', function (Blueprint $table) {
            $table->unsignedInteger('dominion_id');
            $table->string('land_type');
            $table->integer('amount');
            $table->integer('hours');
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
        Schema::dropIfExists('queue_land_incoming');
    }
}
