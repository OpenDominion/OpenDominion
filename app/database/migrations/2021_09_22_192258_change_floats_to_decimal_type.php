<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFloatsToDecimalType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->decimal('spy_strength', 6, 3)->change();
            $table->decimal('wizard_strength', 6, 3)->change();
            $table->decimal('resource_boats', 10, 4)->change();
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
            $table->float('spy_strength')->change();
            $table->float('wizard_strength')->change();
            $table->float('resource_boats')->change();
        });
    }
}
