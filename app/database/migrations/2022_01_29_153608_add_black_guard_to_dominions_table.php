<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBlackGuardToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->timestamp('black_guard_active_at')
                ->nullable()
                ->after('elite_guard_active_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('black_guard_active_at');
        });
    }
}
