<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateValuablesTrackingTable extends Migration
{
    public function up(): void
    {
        Schema::create('valuables_tracking', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('round_id');
            $table->unsignedInteger('source_dominion_id');
            $table->unsignedInteger('target_dominion_id');
            $table->unsignedSmallInteger('progress')->default(0);
            $table->timestamp('last_discovered_at')->nullable();
            $table->timestamps();

            $table->unique(['round_id', 'source_dominion_id', 'target_dominion_id'], 'valuables_tracking_round_source_target_unique');

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('source_dominion_id')->references('id')->on('dominions');
            $table->foreign('target_dominion_id')->references('id')->on('dominions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valuables_tracking');
    }
}
