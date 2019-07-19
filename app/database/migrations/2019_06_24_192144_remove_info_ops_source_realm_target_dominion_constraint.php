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
            $table->boolean('latest')->default(true);

            $table->index(['source_realm_id']);
            $table->index(['target_dominion_id']);
            $table->dropUnique(['source_realm_id', 'target_dominion_id', 'type']);
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
            $table->dropColumn(['latest']);

            $table->unique(['source_realm_id', 'target_dominion_id', 'type']);
            $table->dropIndex(['source_realm_id']);
            $table->dropIndex(['target_dominion_id']);
        });
    }
}
