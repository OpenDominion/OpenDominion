<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDominionHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dominion_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('dominion_id');
            $table->string('event');
            $table->text('delta');
            $table->timestamp('created_at')->nullable();

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
        Schema::dropIfExists('dominion_history');
    }
}
