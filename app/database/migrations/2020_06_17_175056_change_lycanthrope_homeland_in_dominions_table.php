<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeLycanthropeHomelandInDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $race_id = DB::table('races')->where('name', 'Lycanthrope')->pluck('id')->first();
        if ($race_id !== null) {
            DB::table('dominions')->where('race_id', $race_id)->update([
                'land_cavern' => DB::raw('land_cavern - building_home'),
                'land_forest' => DB::raw('land_forest + building_home')
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $race_id = DB::table('races')->where('name', 'Lycanthrope')->pluck('id')->first();
        if ($race_id !== null) {
            DB::table('dominions')->where('race_id', $race_id)->update([
                'land_forest' => DB::raw('land_forest - building_home'),
                'land_cavern' => DB::raw('land_cavern + building_home')
            ]);
        }
    }
}
