<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();

//            $table->integer('active_dominion_id')->unsigned()->nullable()->default(null);

            $table->string('email');
            $table->string('password');
//            $table->string('activation_token', 8);
            $table->string('remember_token')->nullable()->default(null);
            $table->string('display_name', 20);

            $table->unique('email');
            $table->unique('display_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
