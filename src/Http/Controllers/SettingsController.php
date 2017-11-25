<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Image;
use Intervention\Image\Constraint;
use OpenDominion\Helpers\NotificationHelper;

class SettingsController extends AbstractController
{
    public function getIndex()
    {
        return view('pages.settings', [
            'notificationHelper' => app(NotificationHelper::class),
        ]);
    }

    public function postIndex(Request $request)
    {
        if ($newAvatar = $request->file('avatar')) {
            return $this->handleAvatarUpload($newAvatar);
        }

        // notifications

        // security / password

//        dd($request);
    }

    protected function handleAvatarUpload(UploadedFile $file)
    {
        // Convert image
        $image = Image::make($file)
            ->fit(200, 200)
            ->encode('png');

        $path = 'uploads/avatars';
        $fileName = (str_random(40) . '.png');
        $data = (string)$image;

        // todo: bool check
        if (!\Storage::disk('public')->put($path . '/' . $fileName, $data)) {
            throw new \RuntimeException('Failed to upload avatar');
        }

        $user = \Auth::user();
        $user->avatar = $fileName;
        $user->save();

        dd($image);
    }
}
