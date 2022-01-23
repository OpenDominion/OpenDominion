<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('active_spells', function (Blueprint $table) {
            $table->unsignedInteger('spell_id')->after('spell');

            $table->dropForeign('active_spells_dominion_id_foreign');
            $table->dropForeign('active_spells_cast_by_dominion_id_foreign');
            $table->dropPrimary();
        });

        Schema::rename('active_spells', 'dominion_spells');

        DB::table('dominion_spells')->update([
            'spell_id' => DB::raw('(SELECT `id` FROM `spells` WHERE `key` = `dominion_spells`.`spell`)'),
        ]);

        Schema::table('dominion_spells', function (Blueprint $table) {
            $table->dropColumn('spell');

            $table->primary(['dominion_id', 'spell_id']);
            $table->foreign('spell_id')->references('id')->on('spells');
            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('cast_by_dominion_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominion_spells', function (Blueprint $table) {
            $table->string('spell');

            $table->dropForeign('dominion_spells_spell_id_foreign');
            $table->dropForeign('dominion_spells_dominion_id_foreign');
            $table->dropForeign('dominion_spells_cast_by_dominion_id_foreign');
            $table->dropPrimary();
        });

        Schema::rename('dominion_spells', 'active_spells');

        DB::table('active_spells')->update([
            'spell' => DB::raw('(SELECT `key` FROM `spells` WHERE `id` = `active_spells`.`spell_id`)'),
        ]);

        Schema::table('active_spells', function (Blueprint $table) {
            $table->dropColumn('spell_id');

            $table->primary(['dominion_id', 'spell']);
            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('cast_by_dominion_id')->references('id')->on('dominions');
        });
    }
}
