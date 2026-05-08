<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceToDominionTechsTable extends Migration
{
    public function up()
    {
        Schema::table('dominion_techs', function (Blueprint $table) {
            $table->string('source_type')->nullable();
            $table->unsignedInteger('source_id')->nullable();
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down()
    {
        Schema::table('dominion_techs', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
}
