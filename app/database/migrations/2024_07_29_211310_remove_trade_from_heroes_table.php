<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveTradeFromHeroesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('heroes')->update(['class' => DB::raw('trade')]);

        Schema::table('heroes', function (Blueprint $table) {
            $table->dropColumn('trade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->string('trade')->after('class')->nullable();
        });

        DB::table('heroes')->update(['trade' => DB::raw('class')]);
        DB::table('heroes')->update(['class' => 'warrior']);
    }
}
