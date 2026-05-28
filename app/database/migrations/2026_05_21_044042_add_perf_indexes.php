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
        Schema::table('council_threads', function (Blueprint $table) {
            $table->index('last_activity');
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->index('last_activity');
        });

        Schema::table('message_board_threads', function (Blueprint $table) {
            $table->index('last_activity');
        });

        Schema::table('game_events', function (Blueprint $table) {
            $table->index(['round_id', 'created_at']);
        });

        Schema::table('dominion_history', function (Blueprint $table) {
            $table->index(['dominion_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('council_threads', function (Blueprint $table) {
            $table->dropIndex(['last_activity']);
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropIndex(['last_activity']);
        });

        Schema::table('message_board_threads', function (Blueprint $table) {
            $table->dropIndex(['last_activity']);
        });

        Schema::table('game_events', function (Blueprint $table) {
            $table->dropIndex(['round_id', 'created_at']);
        });

        Schema::table('dominion_history', function (Blueprint $table) {
            $table->dropIndex(['dominion_id', 'created_at']);
        });
    }
};
