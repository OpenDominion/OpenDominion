<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorQueues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create table
        Schema::create('dominion_queue', function (Blueprint $table) {
            $table->unsignedInteger('dominion_id');
            $table->string('source');
            $table->string('resource');
            $table->integer('hours');
            $table->integer('amount');
            $table->timestamp('created_at')->nullable();

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'source', 'resource', 'hours']);
        });

        // Migrate data
        DB::transaction(function () {

            // Construction
            foreach (DB::table('queue_construction')->get() as $row) {
                DB::table('dominion_queue')->insert([
                    'dominion_id' => $row->dominion_id,
                    'source' => 'construction',
                    'resource' => "building_{$row->building}",
                    'hours' => $row->hours,
                    'amount' => $row->amount,
                    'created_at' => $row->created_at,
                ]);
            }

            // Exploration
            foreach (DB::table('queue_exploration')->get() as $row) {
                DB::table('dominion_queue')->insert([
                    'dominion_id' => $row->dominion_id,
                    'source' => 'exploration',
                    'resource' => "land_{$row->land_type}",
                    'hours' => $row->hours,
                    'amount' => $row->amount,
                    'created_at' => $row->created_at,
                ]);
            }

            // Training
            foreach (DB::table('queue_training')->get() as $row) {
                DB::table('dominion_queue')->insert([
                    'dominion_id' => $row->dominion_id,
                    'source' => 'training',
                    'resource' => "military_{$row->unit_type}",
                    'hours' => $row->hours,
                    'amount' => $row->amount,
                    'created_at' => $row->created_at,
                ]);
            }

        });

        // Drop old queue tables
        Schema::drop('queue_construction');
        Schema::drop('queue_exploration');
        Schema::drop('queue_land_incoming');
        Schema::drop('queue_training');
        Schema::drop('queue_units_returning');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // todo. maybe

//        Schema::dropIfExists('dominion_queue');
    }
}
