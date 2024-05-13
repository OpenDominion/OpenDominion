<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeBlackOpsStatsOnDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn(['infamy', 'spy_resilience', 'wizard_resilience']);
            $table->unsignedInteger('resilience')->after('wizard_mastery')->default(0);
            $table->unsignedInteger('fireball_meter')->after('resilience')->default(0);
            $table->unsignedInteger('lightning_bolt_meter')->after('fireball_meter')->default(0);
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn(['infamy', 'spy_resilience', 'wizard_resilience']);
            $table->integer('resilience')->after('wizard_strength')->default(0);
            $table->integer('fireball_meter')->after('resilience')->default(0);
            $table->integer('lightning_bolt_meter')->after('fireball_meter')->default(0);
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
            $table->dropColumn(['resilience', 'fireball_meter', 'lightning_bolt_meter']);
            $table->unsignedInteger('infamy')->after('morale')->default(0);
            $table->unsignedInteger('spy_resilience')->after('wizard_strength')->default(0);
            $table->unsignedInteger('wizard_resilience')->after('spy_resilience')->default(0);
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn(['resilience', 'fireball_meter', 'lightning_bolt_meter']);
            $table->unsignedInteger('infamy')->after('morale')->default(0);
            $table->unsignedInteger('spy_resilience')->after('wizard_strength')->default(0);
            $table->unsignedInteger('wizard_resilience')->after('spy_resilience')->default(0);
        });
    }
}
