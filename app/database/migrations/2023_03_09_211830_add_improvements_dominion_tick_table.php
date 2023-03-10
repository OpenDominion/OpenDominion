<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImprovementsDominionTickTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->renameColumn('improvement_towers', 'improvement_spires');
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->integer('improvement_science')->after('resource_boats')->default(0);
            $table->integer('improvement_keep')->after('improvement_science')->default(0);
            $table->integer('improvement_forges')->after('improvement_keep')->default(0);
            $table->integer('improvement_walls')->after('improvement_forges')->default(0);
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
            $table->renameColumn('improvement_spires', 'improvement_towers');
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn([
                'improvement_science',
                'improvement_keep',
                'improvement_forges',
                'improvement_walls'
            ]);
        });
    }
}
