<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuardsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->timestamp('royal_guard_active_at')
                ->nullable()
                ->after('council_last_read');

            $table->timestamp('elite_guard_active_at')
                ->nullable()
                ->after('royal_guard_active_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->dropColumn(['royal_guard_active_at', 'elite_guard_active_at']);
        });
    }
}
