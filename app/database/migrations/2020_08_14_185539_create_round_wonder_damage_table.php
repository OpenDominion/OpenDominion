<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoundWonderDamageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('round_wonder_damage', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('round_wonder_id')->unsigned();
            $table->integer('realm_id')->unsigned();
            $table->integer('dominion_id')->unsigned();
            $table->integer('damage')->default(0);
            $table->timestamps();

            $table->foreign('round_wonder_id')->references('id')->on('round_wonders');
            $table->foreign('realm_id')->references('id')->on('realms');
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
        Schema::dropIfExists('round_wonder_damage');
    }
}
