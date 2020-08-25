<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWonderStatsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_cyclone_damage')->after('stat_lightning_bolt_damage')->default(0);
            $table->unsignedInteger('stat_wonder_damage')->after('stat_spells_reflected')->default(0);
            $table->unsignedInteger('stat_wonders_destroyed')->after('stat_wonder_damage')->default(0);
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
                'stat_cyclone_damage',
                'stat_wonder_damage',
                'stat_wonders_destroyed',
            ]);
        });
    }
}
