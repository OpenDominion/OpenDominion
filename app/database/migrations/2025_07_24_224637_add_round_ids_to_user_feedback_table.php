<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRoundIdsToUserFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_feedback', function (Blueprint $table) {
            $table->integer('round_id')->unsigned()->nullable()->after('target_id');
        });

        // Update existing rows to use the most recent round
        DB::statement('UPDATE user_feedback SET round_id = (SELECT id FROM rounds ORDER BY id DESC LIMIT 1) WHERE round_id IS NULL');

        Schema::table('user_feedback', function (Blueprint $table) {
            $table->integer('round_id')->unsigned()->nullable(false)->change();
            $table->foreign('round_id')->references('id')->on('rounds');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_feedback', function (Blueprint $table) {
            $table->dropForeign(['round_id']);
            $table->dropColumn('round_id');
        });
    }
}
