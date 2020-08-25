<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoundWondersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('round_wonders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('round_id')->unsigned();
            $table->integer('realm_id')->unsigned()->nullable();
            $table->integer('wonder_id')->unsigned();
            $table->integer('power')->default(0);
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('realm_id')->references('id')->on('realms');
            $table->foreign('wonder_id')->references('id')->on('wonders');
            $table->unique(['round_id', 'realm_id']);
            $table->unique(['round_id', 'wonder_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('round_wonders');
    }
}
