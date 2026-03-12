<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Image;
use OpenDominion\Helpers\DiscordHelper;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\User;
use RuntimeException;
use Storage;
use Throwable;

class SettingsController extends AbstractController
{
    public function getIndex()
    {
        $user = Auth::user();

        $notificationHelper = app(NotificationHelper::class);
        $discordHelper = app(DiscordHelper::class);

        $notificationSettings = $user->settings['notifications'] ?? $notificationHelper->getDefaultUserNotificationSettings();

        return view('pages.settings', [
            'notificationHelper' => $notificationHelper,
            'notificationSettings' => $notificationSettings,
            'discordHelper' => $discordHelper
        ]);
    }

    public function postIndex(Request $request)
    {
        if ($newAvatar = $request->file('account_avatar')) {
            try {
                $this->handleAvatarUpload($newAvatar);

            } catch (Throwable $e) {
                $request->session()->flash('alert-danger', $e->getMessage());
                return redirect()->back();
            }
        }

        $this->updateUser($request->input());
        $this->updateNotifications($request->input());
//        $this->updateNotificationSettings($request->input());

        $request->session()->flash('alert-success', 'Your settings have been updated.');
        return redirect()->route('settings');
    }

    protected function handleAvatarUpload(UploadedFile $file)
    {
        /** @var User $user */
        $user = Auth::user();

        // Convert image
        $image = Image::make($file)
            ->fit(200, 200)
            ->encode('png');

        $data = (string)$image;
        $path = 'uploads/avatars';
        $fileName = (Str::slug($user->display_name) . '.png');

        if (!Storage::disk('public')->put(($path . '/' . $fileName), $data)) {
            throw new RuntimeException('Failed to upload avatar');
        }

        $user->avatar = $fileName;
        $user->save();
    }

    protected function updateUser(array $data)
    {
        /** @var User $user */
        $user = Auth::user();

        if (in_array($data['skin'], ['skin-blue', 'skin-classic'])) {
            $user->skin = $data['skin'];
        }

        $settings = ($user->settings ?? []);
        if (isset($data['packadvisors'])) {
            $settings['packadvisors'] = true;
        } else {
            $settings['packadvisors'] = false;
        }
        if (isset($data['realmadvisors'])) {
            $settings['realmadvisors'] = true;
        } else {
            $settings['realmadvisors'] = false;
        }
        if (isset($data['shareusername'])) {
            $settings['shareusername'] = true;
        } else {
            $settings['shareusername'] = false;
        }
        $user->settings = $settings;

        $user->save();
    }

    protected function updateNotifications(array $data)
    {
        if (!isset($data['notifications']) || empty($data['notifications'])) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        /** @var NotificationHelper $notificationHelper */
        $notificationHelper = app(NotificationHelper::class);
        $notificationCategories = $notificationHelper->getNotificationCategories();

        $newNotifications = [];

        // Get list of all ingame notifications (for default values)
        foreach ($notificationCategories as $key => $types) {
            foreach ($types as $type => $channels) {
                Arr::set($newNotifications, "{$key}.{$type}.ingame", false);
                Arr::set($newNotifications, "{$key}.{$type}.email", false);
            }
        }

        // Set checked boxes to true
        foreach ($data['notifications'] as $key => $types) {
            foreach ($types as $type => $channels) {
                foreach ($channels as $channel => $enabled) {
                    if ($enabled === 'on') {
                        Arr::set($newNotifications, "{$key}.{$type}.{$channel}", true);
                    }
                }
            }
        }

        $settings = ($user->settings ?? []);
        $settings['notifications'] = $newNotifications;

        $user->settings = $settings;
        $user->save();
    }

    protected function updateNotificationSettings(array $data)
    {
        /** @var User $user */
        $user = Auth::user();

        $settings = ($user->settings ?? []);
        $settings['notification_digest'] = $data['notification_digest'];

        $user->settings = $settings;
        $user->save();
    }
}
