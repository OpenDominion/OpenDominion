<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateValorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('valor', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('round_id');
            $table->unsignedInteger('realm_id');
            $table->unsignedInteger('dominion_id');
            $table->string('source');
            $table->float('amount');
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('realm_id')->references('id')->on('realms');
            $table->foreign('dominion_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('valor');
    }
}
