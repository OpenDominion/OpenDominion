<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeroesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('heroes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dominion_id')->unsigned();
            $table->string('name')->nullable();
            $table->string('class')->nullable();
            //$table->integer('class_id')->unsigned()->nullable();
            $table->string('trade')->nullable();
            //$table->integer('trade_id')->unsigned()->nullable();
            $table->integer('experience')->default(0);
            //$table->integer('level')->default(0);
            //$table->timestamp('returning_at')->nullable();
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');
            //$table->foreign('class_id')->references('id')->on('hero_classes');
            //$table->foreign('trade_id')->references('id')->on('hero_trades');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('heroes');
    }
}
