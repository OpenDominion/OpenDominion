<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWonderPerksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wonder_perks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wonder_id')->unsigned();
            $table->integer('wonder_perk_type_id')->unsigned();
            $table->string('value')->nullable();
            $table->timestamps();

            $table->foreign('wonder_id')->references('id')->on('wonders');
            $table->foreign('wonder_perk_type_id')->references('id')->on('wonder_perk_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wonder_perks');
    }
}
