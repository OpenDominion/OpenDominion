<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpellPerksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spell_perks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('spell_id')->unsigned();
            $table->integer('spell_perk_type_id')->unsigned();
            $table->string('value')->nullable();
            $table->timestamps();

            $table->foreign('spell_id')->references('id')->on('spells');
            $table->foreign('spell_perk_type_id')->references('id')->on('spell_perk_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spell_perks');
    }
}
