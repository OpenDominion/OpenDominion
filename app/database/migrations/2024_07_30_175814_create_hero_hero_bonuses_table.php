<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeroHeroBonusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hero_hero_bonuses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('hero_id');
            $table->unsignedInteger('hero_bonus_id');
            $table->timestamps();

            $table->foreign('hero_id')->references('id')->on('heroes');
            $table->foreign('hero_bonus_id')->references('id')->on('hero_bonuses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hero_hero_bonuses');
    }
}
