<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Heroes queue for a 1v1 or a 2v2 PvP battle; entries already waiting are 1v1.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('hero_battle_queue', function (Blueprint $table) {
            $table->unsignedTinyInteger('size')->default(1)->after('hero_id');
        });
    }

    public function down(): void
    {
        Schema::table('hero_battle_queue', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
