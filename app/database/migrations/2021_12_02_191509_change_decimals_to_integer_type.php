<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDecimalsToIntegerType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('dominions')->update([
            'spy_strength' => DB::raw('spy_strength * 100'),
            'wizard_strength' => DB::raw('wizard_strength * 100'),
            'resource_boats' => DB::raw('resource_boats * 100')
        ]);

        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('spy_strength')->change();
            $table->unsignedInteger('wizard_strength')->change();
            $table->unsignedInteger('resource_boats')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->decimal('spy_strength', 6, 3)->change();
            $table->decimal('wizard_strength', 6, 3)->change();
            $table->decimal('resource_boats', 10, 4)->change();
        });

        DB::table('dominions')->update([
            'spy_strength' => DB::raw('spy_strength / 100'),
            'wizard_strength' => DB::raw('wizard_strength / 100'),
            'resource_boats' => DB::raw('resource_boats / 100')
        ]);
    }
}
