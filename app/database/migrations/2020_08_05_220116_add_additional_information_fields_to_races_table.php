<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdditionalInformationFieldsToRacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('races', function (Blueprint $table) {
            $table->integer('attacker_difficulty')->after('description')->default(0);
            $table->integer('explorer_difficulty')->after('attacker_difficulty')->default(0);
            $table->integer('converter_difficulty')->after('explorer_difficulty')->default(0);
            $table->integer('overall_difficulty')->after('converter_difficulty')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn('attacker_difficulty');
            $table->dropColumn('explorer_difficulty');
            $table->dropColumn('converter_difficulty');
            $table->dropColumn('overall_difficulty');
        });
    }
}
