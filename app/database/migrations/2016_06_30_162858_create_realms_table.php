<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRealmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('realms', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('round_id')->unsigned();
            $table->integer('monarch_dominion_id')->unsigned()->nullable();
            $table->enum('alignment', ['good', 'neutral', 'evil']);
            $table->integer('number');
            $table->string('name')->nullable();
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
            // todo: monarch_dominion_id
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('realms');
    }
}
