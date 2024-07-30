<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeroBonusPerksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hero_bonus_perks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('hero_bonus_id');
            $table->string('key');
            $table->string('value')->nullable();
            $table->timestamps();

            $table->foreign('hero_bonus_id')->references('id')->on('hero_bonuses');
            $table->unique(['hero_bonus_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hero_bonus_perks');
    }
}
