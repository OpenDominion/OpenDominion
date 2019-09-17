<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UseSoftDeletesForCouncil extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('council_threads', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('council_posts', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('council_threads', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('council_posts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
