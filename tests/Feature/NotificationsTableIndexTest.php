<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use OpenDominion\Tests\AbstractTestCase;

class NotificationsTableIndexTest extends AbstractTestCase
{
    /**
     * The unread-notification dropdown filters on read_at and sorts on
     * created_at within a single notifiable, so both columns have to trail the
     * morph key in that order for the index to serve the query without a
     * filesort. Asserting the exact column order guards against a future
     * migration reordering or dropping it.
     */
    public function testNotificationsTableHasUnreadCoveringIndex(): void
    {
        $index = collect(Schema::getIndexes('notifications'))
            ->firstWhere('name', 'notifications_unread_index');

        $this->assertNotNull(
            $index,
            'Expected the notifications_unread_index covering index to exist on the notifications table.'
        );

        $this->assertSame(
            ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'],
            $index['columns'],
            'notifications_unread_index must keep its column order to serve the unread lookup.'
        );
    }
}
