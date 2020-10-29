<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveForumAnnouncementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('forum_announcements');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('forum_announcements', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('round_id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
        });
    }
}
