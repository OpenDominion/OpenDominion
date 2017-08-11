<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouncilThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('council_threads', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('realm_id');
            $table->unsignedInteger('dominion_id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();

            $table->foreign('realm_id')->references('id')->on('realms');
            $table->foreign('dominion_id')->references('id')->on('dominion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('council_threads');
    }
}
