<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('race_id')->unsigned();
            $table->enum('slot', [1, 2, 3, 4]);
            $table->string('name');
            $table->integer('cost_platinum');
            $table->integer('cost_ore');
            $table->float('power_offense');
            $table->float('power_defense');
            $table->boolean('need_boat')->default(true);
            $table->integer('unit_perk_type_id')->unsigned()->nullable();
            $table->string('unit_perk_type_values')->nullable();
            $table->timestamps();

            $table->foreign('race_id')->references('id')->on('races');

            $table->unique(['race_id', 'slot']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('units');
    }
}
