<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;

class UserController extends AbstractController
{
    public function index()
    {
        $users = User::all();

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
}
