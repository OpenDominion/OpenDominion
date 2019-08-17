<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOffensiveActionsEndDateToRound extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('rounds', static function (Blueprint $table) {
            $table->dateTime('offensive_actions_prohibited_at')
                ->nullable()
                ->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('rounds', static function (Blueprint $table) {
            $table->dropColumn('offensive_actions_prohibited_at');
        });
    }
}
