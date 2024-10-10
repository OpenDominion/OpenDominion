<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCasualtyStatisticsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_military_unit1_lost')->after('stat_wizards_lost')->default(0);
            $table->unsignedInteger('stat_military_unit2_lost')->after('stat_military_unit1_lost')->default(0);
            $table->unsignedInteger('stat_military_unit3_lost')->after('stat_military_unit2_lost')->default(0);
            $table->unsignedInteger('stat_military_unit4_lost')->after('stat_military_unit3_lost')->default(0);
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
                'stat_military_unit1_lost',
                'stat_military_unit2_lost',
                'stat_military_unit3_lost',
                'stat_military_unit4_lost'
            ]);
        });
    }
}
