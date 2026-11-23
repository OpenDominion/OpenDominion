<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Techniques, class abilities and gear move out of hero upgrades into a combat loadout chosen
 * on the hero. Their upgrade rows are removed because data sync never deletes rows.
 */
return new class extends Migration {
    private const COMBAT_UPGRADE_TYPES = ['technique', 'ability', 'gear'];

    public function up(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->json('loadout')->nullable()->after('class_data');
        });

        $upgradeIds = DB::table('hero_upgrades')->whereIn('type', self::COMBAT_UPGRADE_TYPES)->pluck('id');

        DB::table('hero_hero_upgrades')->whereIn('hero_upgrade_id', $upgradeIds)->delete();
        DB::table('hero_upgrade_perks')->whereIn('hero_upgrade_id', $upgradeIds)->delete();
        DB::table('hero_upgrades')->whereIn('id', $upgradeIds)->delete();
    }

    public function down(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->dropColumn('loadout');
        });
    }
};
