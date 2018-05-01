<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeQueueTablesDurationSigned extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('active_spells', function (Blueprint $table) {
            $table->integer('duration')->change();
        });

        Schema::table('queue_construction', function (Blueprint $table) {
            $table->integer('hours')->change();
        });

        Schema::table('queue_exploration', function (Blueprint $table) {
            $table->integer('hours')->change();
        });

        Schema::table('queue_training', function (Blueprint $table) {
            $table->integer('hours')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('active_spells', function (Blueprint $table) {
            $table->unsignedInteger('duration')->change();
        });

        Schema::table('queue_construction', function (Blueprint $table) {
            $table->unsignedInteger('hours')->change();
        });

        Schema::table('queue_exploration', function (Blueprint $table) {
            $table->unsignedInteger('hours')->change();
        });

        Schema::table('queue_training', function (Blueprint $table) {
            $table->unsignedInteger('hours')->change();
        });
    }
}
