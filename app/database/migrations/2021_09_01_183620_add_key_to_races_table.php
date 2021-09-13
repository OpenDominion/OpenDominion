<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        // foreach (Race::all() as $race) { $race->key = str_slug($race->name); $race->save(); }
        // Race::where('name', 'Dark Elf')->update(['key' => 'dark-elf-rework']);
        // Race::where('name', 'Spirit')->update(['key' => 'spirit-rework']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('key');
        });
    }
}
