<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarToRealmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->integer('war_realm_id')->after('name')->unsigned()->nullable();
            $table->timestamp('war_active_at')->after('war_realm_id')->nullable();

            $table->foreign('war_realm_id')->references('id')->on('realms');
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
            $table->dropColumn('war_realm_id');
            $table->dropColumn('war_active_at');
        });
    }
}
