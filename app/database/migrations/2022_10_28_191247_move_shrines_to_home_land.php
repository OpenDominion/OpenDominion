<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoveShrinesToHomeLand extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $plain_race_ids = DB::table('races')->where('home_land_type', 'plain')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $plain_race_ids)->update([
            'land_hill' => DB::raw('land_hill - building_shrine'),
            'land_plain' => DB::raw('land_plain + building_shrine')
        ]);

        $mountain_race_ids = DB::table('races')->where('home_land_type', 'mountain')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $mountain_race_ids)->update([
            'land_hill' => DB::raw('land_hill - building_shrine'),
            'land_mountain' => DB::raw('land_mountain + building_shrine')
        ]);

        $swamp_race_ids = DB::table('races')->where('home_land_type', 'swamp')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $swamp_race_ids)->update([
            'land_hill' => DB::raw('land_hill - building_shrine'),
            'land_swamp' => DB::raw('land_swamp + building_shrine')
        ]);

        $cavern_race_ids = DB::table('races')->where('home_land_type', 'cavern')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $cavern_race_ids)->update([
            'land_hill' => DB::raw('land_hill - building_shrine'),
            'land_cavern' => DB::raw('land_cavern + building_shrine')
        ]);

        $forest_race_ids = DB::table('races')->where('home_land_type', 'forest')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $forest_race_ids)->update([
            'land_hill' => DB::raw('land_hill - building_shrine'),
            'land_forest' => DB::raw('land_forest + building_shrine')
        ]);

        $water_race_ids = DB::table('races')->where('home_land_type', 'water')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $water_race_ids)->update([
            'land_hill' => DB::raw('land_hill - building_shrine'),
            'land_water' => DB::raw('land_water + building_shrine')
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $plain_race_ids = DB::table('races')->where('home_land_type', 'plain')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $plain_race_ids)->update([
            'land_hill' => DB::raw('land_hill + building_shrine'),
            'land_plain' => DB::raw('land_plain - building_shrine')
        ]);

        $mountain_race_ids = DB::table('races')->where('home_land_type', 'mountain')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $mountain_race_ids)->update([
            'land_hill' => DB::raw('land_hill + building_shrine'),
            'land_mountain' => DB::raw('land_mountain - building_shrine')
        ]);

        $swamp_race_ids = DB::table('races')->where('home_land_type', 'swamp')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $swamp_race_ids)->update([
            'land_hill' => DB::raw('land_hill + building_shrine'),
            'land_swamp' => DB::raw('land_swamp - building_shrine')
        ]);

        $cavern_race_ids = DB::table('races')->where('home_land_type', 'cavern')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $cavern_race_ids)->update([
            'land_hill' => DB::raw('land_hill + building_shrine'),
            'land_cavern' => DB::raw('land_cavern - building_shrine')
        ]);

        $forest_race_ids = DB::table('races')->where('home_land_type', 'forest')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $forest_race_ids)->update([
            'land_hill' => DB::raw('land_hill + building_shrine'),
            'land_forest' => DB::raw('land_forest - building_shrine')
        ]);

        $water_race_ids = DB::table('races')->where('home_land_type', 'water')->pluck('id');
        DB::table('dominions')->whereIn('race_id', $water_race_ids)->update([
            'land_hill' => DB::raw('land_hill + building_shrine'),
            'land_water' => DB::raw('land_water - building_shrine')
        ]);
    }
}
