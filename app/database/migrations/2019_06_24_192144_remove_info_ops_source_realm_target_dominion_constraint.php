<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

            $table->boolean('latest')->default(true);

            $table->foreign('source_realm_id')->references('id')->on('realms');
            $table->foreign('target_dominion_id')->references('id')->on('dominions');

            $table->index(['source_realm_id', 'target_dominion_id', 'type']);
            $table->index(['source_realm_id', 'target_dominion_id', 'latest']);
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
            $table->dropForeign(['source_realm_id']);
            $table->dropForeign(['target_dominion_id']);

            $table->dropIndex(['source_realm_id', 'target_dominion_id', 'type']);
            $table->dropIndex(['source_realm_id', 'target_dominion_id', 'latest']);
            $table->dropColumn(['latest']);

            $table->unique(['source_realm_id', 'target_dominion_id', 'type']);
            $table->foreign('source_realm_id')->references('id')->on('realms');
            $table->foreign('target_dominion_id')->references('id')->on('dominions');
        });
    }
}
