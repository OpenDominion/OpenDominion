<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWondersLastSeenToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dateTime('wonders_last_seen')
                ->nullable()
                ->after('town_crier_last_seen');
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
            $table->dropColumn('wonders_last_seen');
        });
    }
}
