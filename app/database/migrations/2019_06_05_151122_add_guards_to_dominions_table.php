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
            $table->timestamp('royal_guard')->nullable();
            $table->timestamp('elite_guard')->nullable();
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
            $table->dropColumn(['royal_guard', 'elite_guard']);
        });
    }
}
