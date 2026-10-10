<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('round_setup_runs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('round_id');
            $table->string('operation', 64);
            $table->dateTime('completed_at');
            $table->timestamps();
            $table->unique(['round_id', 'operation']);
            $table->foreign('round_id')->references('id')->on('rounds')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_setup_runs');
    }
};
