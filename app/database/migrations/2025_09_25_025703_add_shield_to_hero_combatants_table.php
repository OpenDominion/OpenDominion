<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShieldToHeroCombatantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hero_combatants', function (Blueprint $table) {
            $table->unsignedInteger('shield')->after('recover')->default(0);
            $table->text('status')->nullable()->after('abilities');
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
            $table->dropColumn('shield');
            $table->dropColumn('status');
        });
    }
}
