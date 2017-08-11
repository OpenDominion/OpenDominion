<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouncilPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('council_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('council_thread_id');
            $table->unsignedInteger('dominion_id');
            $table->text('body');
            $table->timestamps();

            $table->foreign('council_thread_id')->references('id')->on('council_threads');
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
        Schema::dropIfExists('council_posts');
    }
}
