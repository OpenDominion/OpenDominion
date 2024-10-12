<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;

class UserController extends AbstractController
{
    public function index()
    {
        $users = User::paginate(100);

        return view('pages.staff.administrator.users.index', [
            'users' => $users,
        ]);
    }

    public function show(User $user)
    {
        return view('pages.staff.administrator.users.show', [
            'user' => $user,
        ]);
    }

    public function takeOver(User $user)
    {
        auth()->login($user);

        return redirect()->route('dashboard');
    }
}
