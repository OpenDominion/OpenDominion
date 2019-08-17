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
    public function up(): void
    {
        Schema::table('info_ops', static function (Blueprint $table) {
            $table->boolean('latest')
                ->after('data')
                ->default(true);

            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('info_ops_source_realm_id_foreign');
                $table->dropForeign('info_ops_target_dominion_id_foreign');
            }

            $table->dropUnique(['source_realm_id', 'target_dominion_id', 'type']);

            $table->foreign('source_realm_id')->references('id')->on('realms');
            $table->foreign('target_dominion_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('info_ops', static function (Blueprint $table) {
            $table->unique(['source_realm_id', 'target_dominion_id', 'type']);

            $table->dropColumn(['latest']);
        });
    }
}
