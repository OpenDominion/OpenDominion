<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDiscordUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_discord_users', function (Blueprint $table) {

            $table->increments('id');

            $table->integer('user_id')->unsigned()->unique();
            $table->bigInteger('discord_user_id')->unsigned()->unique();
            $table->string('username');
            $table->integer('discriminator')->unsigned();
            $table->string('email');
            $table->string('refresh_token');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_discord_users');
    }
}
