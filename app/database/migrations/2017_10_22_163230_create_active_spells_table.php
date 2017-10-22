<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveSpellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_spells', function (Blueprint $table) {
            $table->unsignedInteger('dominion_id');
            $table->string('spell');
            $table->unsignedInteger('duration');
            $table->unsignedInteger('cast_by_dominion_id')->nullable();
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('cast_by_dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'spell']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('active_spells');
    }
}
