<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStartDateInRounds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('rounds')->update([
            'start_date' => DB::raw('`start_date` + INTERVAL 72 HOUR'),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('rounds')->update([
            'start_date' => DB::raw('`start_date` - INTERVAL 72 HOUR'),
        ]);
    }
}
