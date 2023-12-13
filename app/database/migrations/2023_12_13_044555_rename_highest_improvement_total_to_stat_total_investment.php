<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameHighestImprovementTotalToStatTotalInvestment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('highest_improvement_total');
            $table->unsignedInteger('stat_total_investment')->after('stat_total_gems_spent_investment')->default(0);
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
            $table->dropColumn('stat_total_investment');
            $table->unsignedInteger('highest_improvement_total')->after('highest_land_achieved')->default(0);
        });
    }
}
