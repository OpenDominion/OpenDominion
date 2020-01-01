<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDominionTechsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dominion_techs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dominion_id')->unsigned();
            $table->integer('tech_id')->unsigned();
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('tech_id')->references('id')->on('techs');
            $table->unique(['dominion_id', 'tech_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dominion_techs');
    }
}
