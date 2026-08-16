<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Supports the ranking window in TickService::updateDailyRankings().
     *
     * The table previously carried only unique(dominion_id, key) and the
     * round_id foreign-key index, so ranking a round had nothing to seek on.
     *
     * Column order follows what the statement needs: round_id is the only
     * equality predicate and the table accumulates every round ever played;
     * key is the window's partition; value is its sort key; rank is included
     * purely so the derived table can be served index-only, since it selects
     * id, rank, key and value, and InnoDB appends the primary key.
     *
     * The same index serves the leaderboard reads in RankingsController and
     * ValhallaController::getDominionsByRanking(), which filter on
     * round_id + key and order by value.
     */
    public function up(): void
    {
        Schema::table('daily_rankings', function (Blueprint $table) {
            $table->index(['round_id', 'key', 'value', 'rank'], 'daily_rankings_round_key_value_rank_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_rankings', function (Blueprint $table) {
            $table->dropIndex('daily_rankings_round_key_value_rank_idx');
        });
    }
};
