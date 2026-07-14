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
            $table->text('automation_templates')->nullable()->after('ai_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('automation_templates');
        });
    }
};
