<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTechVersioning extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('techs', function (Blueprint $table) {
            $table->unsignedInteger('version')->after('prerequisites')->default(1);
            $table->unsignedInteger('x')->after('version')->default(0);
            $table->unsignedInteger('y')->after('x')->default(0);
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedInteger('tech_version')->after('mixed_alignment')->default(1);
        });

        DB::table('rounds')->where('start_date', '>', '2020-11-01')->update([
            'tech_version' => 2,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('techs', function (Blueprint $table) {
            $table->dropColumn(['version', 'x', 'y']);
        });

        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn('tech_version');
        });
    }
}
