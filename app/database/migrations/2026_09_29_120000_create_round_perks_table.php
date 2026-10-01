<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoundPerksTable extends Migration
{
    public function up(): void
    {
        Schema::create('round_perks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('round_id');
            $table->string('key');
            $table->string('value');
            $table->string('alignment')->nullable();
            $table->unsignedSmallInteger('from_day')->nullable();
            $table->unsignedSmallInteger('until_day')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_perks');
    }
}
