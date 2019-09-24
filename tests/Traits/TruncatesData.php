<?php

namespace OpenDominion\Tests\Traits;

use DB;

trait TruncatesData
{
    protected function truncateGameData(): void
    {
        $tables = [
            'active_spells',
            'council_posts',
            'council_threads',
            'daily_rankings',
            'dominion_history',
            'dominion_queue',
            'dominions',
            'game_events',
            'info_ops',
            'packs',
            'realms',
            'rounds',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
