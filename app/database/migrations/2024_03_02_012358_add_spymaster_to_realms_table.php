<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpymasterToRealmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->unsignedInteger('spymaster_dominion_id')->nullable()->after('general_dominion_id');
            $table->foreign('spymaster_dominion_id')->references('id')->on('dominions');
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
            $table->dropForeign('realms_spymaster_dominion_id_foreign');
            $table->dropColumn('spymaster_dominion_id');
        });
    }
}
