<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInfoOpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('info_ops', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('source_realm_id');
            $table->unsignedInteger('source_dominion_id');
            $table->unsignedInteger('target_dominion_id');
            $table->string('type');
            $table->text('data');
            $table->timestamps();

            $table->unique(['source_realm_id', 'target_dominion_id', 'type']);

            $table->foreign('source_realm_id')->references('id')->on('realms');
            $table->foreign('source_dominion_id')->references('id')->on('dominions');
            $table->foreign('target_dominion_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('info_ops');
    }
}
