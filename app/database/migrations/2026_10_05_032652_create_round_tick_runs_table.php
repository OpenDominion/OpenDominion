<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('round_tick_runs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('round_id');
            $table->dateTime('tick_at');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('maintenance_completed_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique('round_id');
            $table->foreign('round_id')->references('id')->on('rounds')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_tick_runs');
    }
};
