<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRaceKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('races')->where('key', 'undead')->update(['key' => 'spirit']);
        DB::table('races')->where('key', 'undead-rework')->update(['key' => 'undead']);
        DB::table('races')->where('key', 'undead-v3')->update(['key' => 'undead-rework']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('races')->where('key', 'undead-rework')->update(['key' => 'undead-v3']);
        DB::table('races')->where('key', 'undead')->update(['key' => 'undead-rework']);
    }
}
