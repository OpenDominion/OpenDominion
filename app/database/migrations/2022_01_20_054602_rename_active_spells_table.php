<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class RenameActiveSpellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('active_spells')->delete();

        Schema::rename('active_spells', 'dominion_spells');

        Schema::table('dominion_spells', function (Blueprint $table) {
            $table->unsignedInteger('spell_id')->after('spell');
            $table->dropForeign('active_spells_dominion_id_foreign');
            $table->dropForeign('active_spells_cast_by_dominion_id_foreign');
            $table->dropPrimary();
            $table->primary(['dominion_id', 'spell_id']);
            $table->foreign('spell_id')->references('id')->on('spells');
            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('cast_by_dominion_id')->references('id')->on('dominions');
        });

        Schema::table('dominion_spells', function (Blueprint $table) {
            $table->dropColumn('spell');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('dominion_spells')->delete();

        Schema::rename('dominion_spells', 'active_spells');

        Schema::table('active_spells', function (Blueprint $table) {
            $table->string('spell');
            $table->dropColumn('spell_id');

            $table->dropForeign('dominion_spells_spell_id_foreign');
            $table->dropForeign('dominion_spells_dominion_id_foreign');
            $table->dropForeign('dominion_spells_cast_by_dominion_id_foreign');
            $table->dropPrimary();
            $table->primary(['dominion_id', 'spell']);
            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('cast_by_dominion_id')->references('id')->on('dominions');
        });
    }
}
