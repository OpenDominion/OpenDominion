<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageBoardThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_board_threads', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('message_board_category_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->string('title');
            $table->text('body');
            $table->boolean('flagged_for_removal')->default(false);
            $table->text('flagged_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('message_board_category_id')->references('id')->on('message_board_categories');
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
        Schema::dropIfExists('message_board_threads');
    }
}
