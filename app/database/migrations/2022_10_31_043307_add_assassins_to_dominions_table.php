<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssassinsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->integer('military_assassins')->after('military_spies')->default(0);
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->integer('military_assassins')->after('military_spies')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn(['military_assassins']);
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn(['military_assassins']);
        });
    }
}
