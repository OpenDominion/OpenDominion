<?php

use Illuminate\Database\Migrations\Migration;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\User;

class SetDefaultNotificationSettings extends Migration
{
    /** @var NotificationHelper */
    protected $notificationHelper;

    /**
     * SetDefaultNotificationSettings constructor.
     */
    public function __construct()
    {
        $this->notificationHelper = app(NotificationHelper::class);
    }

    /**
     * Run the migrations.
     *
     * @return void
     * @throws Throwable
     */
    public function up()
    {
        DB::transaction(function () {
            User::each(function (User $user) {
                $user->settings = [
                    'notifications' => $this->notificationHelper->getDefaultUserNotificationSettings(),
                    'notification_digest' => 'hourly',
                ];
                $user->save();
            });
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws Throwable
     */
    public function down()
    {
        DB::transaction(function () {
            User::each(function (User $user) {
                $user->settings = null;
                $user->save();
            });
        });
    }
}
