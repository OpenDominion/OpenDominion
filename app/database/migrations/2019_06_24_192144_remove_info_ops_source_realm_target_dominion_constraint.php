<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveInfoOpsSourceRealmTargetDominionConstraint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('info_ops', function (Blueprint $table) {
            $table->dropForeign(['source_realm_id']);
            $table->dropForeign(['target_dominion_id']);
            $table->dropUnique(['source_realm_id', 'target_dominion_id', 'type']);

            $table->foreign('source_realm_id')->references('id')->on('realms');
            $table->foreign('target_dominion_id')->references('id')->on('dominions');

            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('info_ops', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->unique(['source_realm_id', 'target_dominion_id', 'type']);
        });
    }
}
