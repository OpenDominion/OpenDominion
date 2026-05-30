<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateValuablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('valuables', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('round_id');
            $table->unsignedInteger('source_dominion_id');
            $table->unsignedInteger('target_dominion_id');
            $table->string('name');
            $table->string('rarity');
            $table->string('type');
            $table->string('status')->default('discovered');
            $table->integer('required_spy_hours');
            $table->integer('spies_assigned')->nullable();
            $table->timestamp('investigation_started_at')->nullable();
            $table->timestamp('investigation_ends_at')->nullable();
            $table->timestamp('stolen_at')->nullable();
            $table->timestamp('discovered_at');
            $table->boolean('is_listed')->default(false);
            $table->integer('sold_price')->nullable();
            $table->boolean('transferred')->default(false);
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('source_dominion_id')->references('id')->on('dominions');
            $table->foreign('target_dominion_id')->references('id')->on('dominions');

            $table->index(['source_dominion_id', 'status']);
            $table->index(['target_dominion_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('valuables');
    }
}
