<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnreadIndexToNotificationsTable extends Migration
{
    /**
     * Covering index for the unread-notification lookup that
     * partials/notification-nav.blade.php performs on every authenticated page
     * request: notifiable_type and notifiable_id matched for equality, read_at
     * filtered on null, created_at sorted descending.
     *
     * The morphs() index from the original create migration only covers the
     * leading two columns, leaving the read_at filter and the created_at sort
     * to a filesort that grows with each round's accumulated notifications.
     */
    private const INDEX_NAME = 'notifications_unread_index';

    /**
     * @var string[]
     */
    private const INDEX_COLUMNS = ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'];

    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(self::INDEX_COLUMNS, self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(self::INDEX_NAME);
        });
    }
}
