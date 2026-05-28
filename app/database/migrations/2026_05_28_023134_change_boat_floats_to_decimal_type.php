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
            $table->decimal('stat_total_boat_production', 10, 4)->default(0)->change();
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->decimal('resource_boats', 10, 4)->default(0)->change();
            $table->decimal('resource_boat_production', 10, 4)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->float('stat_total_boat_production')->default(0)->change();
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->float('resource_boats')->default(0)->change();
            $table->float('resource_boat_production')->default(0)->change();
        });
    }
};
