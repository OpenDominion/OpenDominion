<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackRelatedColumnsToRealms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->boolean('has_pack')->default(false);
            $table->unsignedInteger('reserved_slots')->default(0);
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
            $table->dropColumn(['reserved_slots', 'has_pack']);
        });
    }
}
