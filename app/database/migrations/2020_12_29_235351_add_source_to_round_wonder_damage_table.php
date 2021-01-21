<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceToRoundWonderDamageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('round_wonder_damage', function (Blueprint $table) {
            $table->string('source')->nullable()->after('damage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('round_wonder_damage', function (Blueprint $table) {
            $table->dropColumn('cost_lumber');
        });
    }
}
