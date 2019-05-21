<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitPerksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_perks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('unit_id')->unsigned();
            $table->integer('unit_perk_type_id')->unsigned();
            $table->string('value')->nullable();
            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('units');
            $table->foreign('unit_perk_type_id')->references('id')->on('unit_perk_types');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('unit_perk_type_id');
            $table->dropColumn('unit_perk_type_values');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_perks');

        Schema::table('units', function (Blueprint $table) {
            $table->integer('unit_perk_type_id')->unsigned()->nullable();
            $table->string('unit_perk_type_values')->nullable();
        });
    }
}
