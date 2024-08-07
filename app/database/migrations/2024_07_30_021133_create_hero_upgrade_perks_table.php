<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeroUpgradePerksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hero_upgrade_perks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('hero_upgrade_id');
            $table->string('key');
            $table->string('value')->nullable();
            $table->timestamps();

            $table->foreign('hero_upgrade_id')->references('id')->on('hero_upgrades');
            $table->unique(['hero_upgrade_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hero_upgrade_perks');
    }
}
