<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValorColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedInteger('largest_hit')->after('tech_version')->default(0);
        });

        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('valor')->after('morale')->default(0);
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->unsignedInteger('valor')->after('rating')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn('largest_hit');
        });

        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('valor');
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn('valor');
        });
    }
}
