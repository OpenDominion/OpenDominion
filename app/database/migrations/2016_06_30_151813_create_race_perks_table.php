<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRacePerksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('race_perks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('race_id')->unsigned();
            $table->integer('race_perk_type_id')->unsigned();
            $table->float('value');
            $table->timestamps();

            $table->foreign('race_id')->references('id')->on('races');
            $table->foreign('race_perk_type_id')->references('id')->on('race_perk_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('race_perks');
    }
}
