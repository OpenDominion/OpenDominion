<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRulerNameToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->string('ruler_name')->nullable()->after('name');
            $table->unique(['round_id', 'realm_id', 'ruler_name']);
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
            $table->dropColumn('ruler_name');
        });
    }
}
