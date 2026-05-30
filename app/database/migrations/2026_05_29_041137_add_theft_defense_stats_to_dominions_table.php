<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_total_platinum_stolen_from')->after('stat_total_platinum_stolen')->default(0);
            $table->unsignedInteger('stat_total_food_stolen_from')->after('stat_total_food_stolen')->default(0);
            $table->unsignedInteger('stat_total_lumber_stolen_from')->after('stat_total_lumber_stolen')->default(0);
            $table->unsignedInteger('stat_total_mana_stolen_from')->after('stat_total_mana_stolen')->default(0);
            $table->unsignedInteger('stat_total_ore_stolen_from')->after('stat_total_ore_stolen')->default(0);
            $table->unsignedInteger('stat_total_gems_stolen_from')->after('stat_total_gems_stolen')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn([
                'stat_total_platinum_stolen_from',
                'stat_total_food_stolen_from',
                'stat_total_lumber_stolen_from',
                'stat_total_mana_stolen_from',
                'stat_total_ore_stolen_from',
                'stat_total_gems_stolen_from',
            ]);
        });
    }
};
