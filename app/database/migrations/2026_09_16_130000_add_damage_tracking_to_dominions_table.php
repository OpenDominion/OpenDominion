<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDamageTrackingToDominionsTable extends Migration
{
    public function up(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('peasants_killed')->default(0)->after('peasants_last_hour');
            $table->unsignedInteger('resolve')->default(0)->after('resilience');
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->integer('resolve')->default(0)->after('resilience');
        });
    }

    public function down(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('peasants_killed');
            $table->dropColumn('resolve');
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn('resolve');
        });
    }
}
