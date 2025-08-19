<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRaidScoreToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_raid_score')->after('stat_spells_deflected')->default(0);
        });

        // Populate raid score for dominions that have raid contributions
        $dominionScores = DB::table('raid_contributions')
            ->select('dominion_id', DB::raw('SUM(score) as total_score'))
            ->groupBy('dominion_id')
            ->get();

        foreach ($dominionScores as $dominionScore) {
            DB::table('dominions')
                ->where('id', $dominionScore->dominion_id)
                ->update(['stat_raid_score' => $dominionScore->total_score]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('stat_raid_score');
        });
    }
}
