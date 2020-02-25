<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalDominionStatisticsFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->unsignedInteger('stat_attacking_failure')->after('stat_attacking_success')->default(0);
            $table->unsignedInteger('stat_defending_failure')->after('stat_defending_success')->default(0);
            $table->unsignedInteger('stat_espionage_failure')->after('stat_espionage_success')->default(0);
            $table->unsignedInteger('stat_spell_failure')->after('stat_spell_success')->default(0);
            $table->unsignedInteger('stat_spies_executed')->after('stat_spell_failure')->default(0);
            $table->unsignedInteger('stat_wizards_executed')->after('stat_spies_executed')->default(0);
            $table->unsignedInteger('stat_spells_reflected')->after('stat_plague_hours')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->dropColumn([
                'stat_attacking_failure',
                'stat_defending_failure',
                'stat_espionage_failure',
                'stat_spell_failure',
                'stat_spies_executed',
                'stat_wizards_executed',
                'stat_spells_reflected',
            ]);
        });
    }
}
