<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTargetRealmIdToInfoOpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('info_ops', static function (Blueprint $table) {
            $table->unsignedInteger('target_realm_id')
                ->nullable()
                ->after('source_dominion_id');

            $table->foreign('target_realm_id')->references('id')->on('realms');
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
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('info_ops_target_realm_id_foreign');
            }

            $table->dropColumn('target_realm_id');
        });
    }
}
