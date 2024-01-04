<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBountiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bounties', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('round_id');
            $table->unsignedInteger('source_realm_id');
            $table->unsignedInteger('source_dominion_id');
            $table->unsignedInteger('target_dominion_id');
            $table->unsignedInteger('collected_by_dominion_id')->nullable();
            $table->string('type');
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('source_realm_id')->references('id')->on('realms');
            $table->foreign('source_dominion_id')->references('id')->on('dominions');
            $table->foreign('target_dominion_id')->references('id')->on('dominions');
            $table->foreign('collected_by_dominion_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bounties');
    }
}
