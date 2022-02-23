<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRatingsToRealmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->unsignedInteger('rating')->after('motd_updated_at')->default(0);
        });

        Schema::table('packs', function (Blueprint $table) {
            $table->unsignedInteger('rating')->after('closed_at')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn('rating');
        });

        Schema::table('packs', function (Blueprint $table) {
            $table->dropColumn('rating');
        });
    }
}
