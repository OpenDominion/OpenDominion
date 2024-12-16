<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEndNameToRealmWarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realm_wars', function (Blueprint $table) {
            $table->string('source_realm_name_end')->nullable()->after('source_realm_name');
            $table->string('target_realm_name_end')->nullable()->after('target_realm_name');
            $table->renameColumn('source_realm_name', 'source_realm_name_start');
            $table->renameColumn('target_realm_name', 'target_realm_name_start');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('realm_wars', function (Blueprint $table) {
            $table->renameColumn('source_realm_name_start', 'source_realm_name');
            $table->renameColumn('target_realm_name_start', 'target_realm_name');
            $table->dropColumn('source_realm_name_end');
            $table->dropColumn('target_realm_name_end');
        });
    }
}
