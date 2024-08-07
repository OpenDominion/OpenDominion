<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeroHeroUpgradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hero_hero_upgrades', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('hero_id');
            $table->unsignedInteger('hero_upgrade_id');
            $table->timestamps();

            $table->foreign('hero_id')->references('id')->on('heroes');
            $table->foreign('hero_upgrade_id')->references('id')->on('hero_upgrades');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hero_hero_upgrades');
    }
}
