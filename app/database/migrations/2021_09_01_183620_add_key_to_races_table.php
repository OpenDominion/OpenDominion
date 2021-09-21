<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use OpenDominion\Models\Race;

class AddKeyToRacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('races', function (Blueprint $table) {
            $table->string('key')->after('id')->nullable();
        });

        foreach (Race::all() as $race) {
            $race->key = str_slug($race->name);
            if (in_array($race->key, ['dark-elf', 'nomad', 'spirit'])) {
                $race->key .= '-rework';
            }
            $race->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn('key');
        });
    }
}
