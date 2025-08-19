<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivePlayerCounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->unsignedTinyInteger('active_player_count')->after('name')->default(0);
        });

        Schema::table('raids', function (Blueprint $table) {
            $table->float('average_active_player_count')->after('completion_reward_amount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn('active_player_count');
        });

        Schema::table('raids', function (Blueprint $table) {
            $table->dropColumn('average_active_player_count');
        });
    }
}