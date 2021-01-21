<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalResourcesToUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('units', function (Blueprint $table) {
            $table->integer('cost_mana')->after('cost_ore');
            $table->integer('cost_lumber')->after('cost_mana');
            $table->integer('cost_gems')->after('cost_lumber');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('cost_mana');
            $table->dropColumn('cost_lumber');
            $table->dropColumn('cost_gems');
        });
    }
}
