<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChaosToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('chaos')->after('valor')->default(0);
            $table->unsignedInteger('stat_incite_chaos_damage')->after('stat_sabotage_boats_damage_received')->default(0);
            $table->unsignedInteger('stat_incite_chaos_damage_received')->after('stat_incite_chaos_damage')->default(0);
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
            $table->dropColumn(['chaos', 'stat_incite_chaos_damage', 'stat_incite_chaos_damage_received']);
        });
    }
}
