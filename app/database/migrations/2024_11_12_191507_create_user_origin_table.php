<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserOriginTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_origin_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('isp')->nullable();
            $table->string('organization')->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->boolean('vpn')->nullable();
            $table->float('score')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();

            $table->unique('ip_address');
        });

        Schema::create('user_origins', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('dominion_id')->nullable();
            $table->string('ip_address');
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('ip_address')->references('ip_address')->on('user_origin_lookups');
            $table->unique(['user_id', 'dominion_id', 'ip_address']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_origins');
        Schema::dropIfExists('user_origin_lookups');
    }
}
