<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDailyXpToDominionsTable extends Migration
{
    public function up(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->decimal('daily_xp', 7, 4)->after('daily_actions')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('daily_xp');
        });
    }
}
