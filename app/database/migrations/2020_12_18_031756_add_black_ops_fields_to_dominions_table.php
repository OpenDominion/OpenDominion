<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBlackOpsFieldsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('infamy')->after('morale')->default(0);
            $table->unsignedInteger('spy_resilience')->after('wizard_strength')->default(0);
            $table->unsignedInteger('wizard_resilience')->after('spy_resilience')->default(0);
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->integer('infamy')->after('morale')->default(0);
            $table->integer('spy_resilience')->after('wizard_strength')->default(0);
            $table->integer('wizard_resilience')->after('spy_resilience')->default(0);
        });

        DB::statement('ALTER TABLE dominions CHANGE COLUMN stat_spy_prestige spy_mastery int(10) unsigned NOT NULL default 0 AFTER wizard_resilience;');
        DB::statement('ALTER TABLE dominions CHANGE COLUMN stat_wizard_prestige wizard_mastery int(10) unsigned NOT NULL default 0 AFTER spy_mastery;');

        DB::table('daily_rankings')->where('key', 'spy-prestige')->update([
            'key' => 'spy-mastery'
        ]);

        DB::table('daily_rankings')->where('key', 'wizard-prestige')->update([
            'key' => 'wizard-mastery'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('infamy');
            $table->dropColumn('spy_resilience');
            $table->dropColumn('wizard_resilience');
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn('infamy');
            $table->dropColumn('spy_resilience');
            $table->dropColumn('wizard_resilience');
        });

        DB::statement('ALTER TABLE dominions CHANGE COLUMN spy_mastery stat_spy_prestige int(10) unsigned NOT NULL default 0 AFTER stat_total_gems_stolen;');
        DB::statement('ALTER TABLE dominions CHANGE COLUMN wizard_mastery stat_wizard_prestige int(10) unsigned NOT NULL default 0 AFTER stat_spy_prestige;');

        DB::table('daily_rankings')->where('key', 'spy-mastery')->update([
            'key' => 'spy-prestige'
        ]);

        DB::table('daily_rankings')->where('key', 'wizard-mastery')->update([
            'key' => 'wizard-prestige'
        ]);
    }
}
