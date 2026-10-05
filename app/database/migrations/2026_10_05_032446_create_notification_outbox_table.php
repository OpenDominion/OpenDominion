<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_outbox', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('operation_key', 100);
            $table->unsignedInteger('dominion_id');
            $table->string('category', 40);
            $table->json('payload');
            $table->timestamp('event_at');
            $table->timestamp('available_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique(['operation_key', 'dominion_id', 'category'], 'notification_outbox_operation_unique');
            $table->index(['delivered_at', 'available_at', 'id'], 'notification_outbox_pending_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_outbox');
    }
};
