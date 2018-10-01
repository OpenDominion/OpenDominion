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
        Schema::create('queue_construction', function (Blueprint $table) {
            $table->integer('dominion_id')->unsigned();
            $table->string('building');
            $table->integer('amount');
            $table->integer('hours');
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'building', 'hours']);
        });

        Schema::create('queue_exploration', function (Blueprint $table) {
            $table->integer('dominion_id')->unsigned();
            $table->string('land_type');
            $table->integer('amount');
            $table->integer('hours');
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'land_type', 'hours']);
        });

        Schema::create('queue_land_incoming', function (Blueprint $table) {
            $table->integer('dominion_id')->unsigned();
            $table->string('land_type');
            $table->integer('amount');
            $table->integer('hours');
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'land_type', 'hours']);
        });

        Schema::create('queue_training', function (Blueprint $table) {
            $table->integer('dominion_id')->unsigned();
            $table->string('unit_type');
            $table->integer('amount');
            $table->integer('hours');
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'unit_type', 'hours']);
        });

        Schema::create('queue_units_returning', function (Blueprint $table) {
            $table->integer('dominion_id')->unsigned();
            $table->string('unit_type');
            $table->integer('amount');
            $table->integer('hours');
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'unit_type', 'hours']);
        });

        DB::transaction(function () {

            foreach (DB::table('dominion_queue')->get() as $row) {
                $table = '';
                $field = '';
                $resource = '';

                switch ($row->source) {
                    case 'construction':
                        $table = 'queue_construction';
                        $field = 'building';
                        $resource = str_replace('building_', '', $row->resource);
                        break;

                    case 'exploration':
                        $table = 'queue_exploration';
                        $field = 'land_type';
                        $resource = str_replace('land_', '', $row->resource);
                        break;

                    case 'training':
                        $table = 'queue_training';
                        $field = 'unit_type';
                        $resource = str_replace('military_', '', $row->resource);
                        break;
                }

                DB::table($table)->insert([
                    'dominion_id' => $row->dominion_id,
                    $field => $resource,
                    'amount' => $row->amount,
                    'hours' => $row->hours,
                ]);
            }
        });

        Schema::dropIfExists('dominion_queue');
    }
}
