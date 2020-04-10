<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorDailyRankings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_rankings', function (Blueprint $table) {
            $table->string('key')->after('realm_name');
            $table->integer('value')->after('key')->default(0);
            $table->unsignedInteger('rank')->after('value')->nullable();
            $table->unsignedInteger('previous_rank')->after('rank')->nullable();

            $table->unique(['dominion_id', 'key']);
        });

        Schema::table('daily_rankings', function (Blueprint $table) {
            $table->dropColumn([
                'land',
                'land_rank',
                'land_rank_change',
                'networth',
                'networth_rank',
                'networth_rank_change'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_rankings', function (Blueprint $table) {
            $table->unsignedInteger('land');
            $table->unsignedInteger('land_rank')->nullable();
            $table->integer('land_rank_change')->nullable();
            $table->unsignedInteger('networth');
            $table->unsignedInteger('networth_rank')->nullable();
            $table->integer('networth_rank_change')->nullable();
        });

        Schema::table('daily_rankings', function (Blueprint $table) {
            $table->dropForeign(['dominion_id']);
            $table->dropUnique(['dominion_id', 'key']);
            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->dropColumn([
                'key',
                'value',
                'rank',
                'previous_rank',
            ]);
        });
    }
}
