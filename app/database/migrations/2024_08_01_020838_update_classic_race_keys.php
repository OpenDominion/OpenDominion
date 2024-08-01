<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateClassicRaceKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('races')->where('key', 'spirit-rework')->update(['key' => 'spirit-legacy']);
        DB::table('races')->where('key', 'undead')->update(['key' => 'undead-legacy']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('races')->where('key', 'spirit-legacy')->update(['key' => 'spirit-rework']);
        DB::table('races')->where('key', 'undead-legacy')->update(['key' => 'undead']);
    }
}
