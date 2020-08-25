<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalStatsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_total_land_lost')->after('stat_total_land_conquered')->default(0);
            $table->unsignedInteger('stat_spies_lost')->after('stat_spies_executed')->default(0);
            $table->unsignedInteger('stat_wizards_lost')->after('stat_wizards_executed')->default(0);
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
            $table->dropColumn([
                'stat_total_land_lost',
                'stat_spies_lost',
                'stat_wizards_lost',
            ]);
        });
    }
}
