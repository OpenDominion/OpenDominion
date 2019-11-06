<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRealmHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('realm_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('realm_id');
            $table->unsignedInteger('dominion_id');
            $table->string('event');
            $table->text('delta');
            $table->timestamp('created_at')->nullable();

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
        Schema::dropIfExists('realm_history');
    }
}
