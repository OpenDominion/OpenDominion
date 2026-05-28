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
        Schema::table('info_ops', function (Blueprint $table) {
            $table->index(['source_realm_id', 'latest', 'created_at'], 'info_ops_realm_latest_created_idx');
            $table->index(['target_dominion_id', 'type', 'created_at'], 'info_ops_target_type_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('info_ops', function (Blueprint $table) {
            $table->dropIndex('info_ops_realm_latest_created_idx');
            $table->dropIndex('info_ops_target_type_created_idx');
        });
    }
};
