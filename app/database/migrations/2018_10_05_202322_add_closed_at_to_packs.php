<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClosedAtToPacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dateTime('closed_at')
                ->nullable()
                ->after('size');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
    }
}
