<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('round_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('realm_id')->nullable();
            $table->string('password');
            $table->unsignedInteger('size');
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('realm_id')->references('id')->on('realms');

            $table->unique(['password', 'round_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('packs');
    }
}
