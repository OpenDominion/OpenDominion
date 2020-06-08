<?php

namespace OpenDominion\Http\Controllers\Staff;

use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;

class StaffController extends AbstractController
{
    public function getIndex()
    {
        $staff = User::has('roles')->get();

        return view('pages.staff.index', [
            'staff' => $staff
        ]);
    }
}
