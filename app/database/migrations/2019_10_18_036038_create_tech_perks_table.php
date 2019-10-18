<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTechPerksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tech_perks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tech_id')->unsigned();
            $table->integer('tech_perk_type_id')->unsigned();
            $table->string('value')->nullable();
            $table->timestamps();

            $table->foreign('tech_id')->references('id')->on('techs');
            $table->foreign('tech_perk_type_id')->references('id')->on('tech_perk_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tech_perks');
    }
}
