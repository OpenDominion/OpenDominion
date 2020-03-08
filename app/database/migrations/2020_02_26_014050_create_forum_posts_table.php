<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForumPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('forum_thread_id');
            $table->unsignedInteger('dominion_id');
            $table->text('body');
            $table->boolean('flagged_for_removal')->default(false);
            $table->text('flagged_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('forum_thread_id')->references('id')->on('forum_threads');
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
        Schema::dropIfExists('forum_posts');
    }
}
