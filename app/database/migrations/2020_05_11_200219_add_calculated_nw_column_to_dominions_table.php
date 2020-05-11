<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCalculatedNwColumnToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('calculated_networth')->after('highest_land_achieved')->default(0);
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->unsignedInteger('calculated_networth')->after('highest_land_achieved')->default(0);
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
            $table->dropColumn('calculated_networth');
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn('calculated_networth');
        });
    }
}
