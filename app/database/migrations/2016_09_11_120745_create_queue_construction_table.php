<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueConstructionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue_construction', function (Blueprint $table) {
            $table->integer('dominion_id')->unsigned();
            $table->string('building');
            $table->integer('amount');
            $table->integer('hours')->unsigned();
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'building', 'hours']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('queue_construction');
    }
}
