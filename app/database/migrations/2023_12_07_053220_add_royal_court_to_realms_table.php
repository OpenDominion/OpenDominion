<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoyalCourtToRealmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->unsignedInteger('general_dominion_id')->nullable()->after('monarch_dominion_id');
            $table->unsignedInteger('magister_dominion_id')->nullable()->after('general_dominion_id');
            $table->unsignedInteger('mage_dominion_id')->nullable()->after('magister_dominion_id');
            $table->unsignedInteger('jester_dominion_id')->nullable()->after('mage_dominion_id');

            $table->foreign('general_dominion_id')->references('id')->on('dominions');
            $table->foreign('magister_dominion_id')->references('id')->on('dominions');
            $table->foreign('mage_dominion_id')->references('id')->on('dominions');
            $table->foreign('jester_dominion_id')->references('id')->on('dominions');
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
            $table->dropForeign('realms_general_dominion_id_foreign');
            $table->dropForeign('realms_magister_dominion_id_foreign');
            $table->dropForeign('realms_mage_dominion_id_foreign');
            $table->dropForeign('realms_jester_dominion_id_foreign');

            $table->dropColumn([
                'general_dominion_id',
                'magister_dominion_id',
                'mage_dominion_id',
                'jester_dominion_id',
            ]);
        });
    }
}
