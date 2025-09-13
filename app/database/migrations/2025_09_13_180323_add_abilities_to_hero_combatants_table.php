<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAbilitiesToHeroCombatantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hero_combatants', function (Blueprint $table) {
            $table->text('abilities')->nullable()->after('strategy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hero_combatants', function (Blueprint $table) {
            $table->dropColumn('abilities');
        });
    }
}
