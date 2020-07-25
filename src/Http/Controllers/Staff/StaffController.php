<?php

namespace OpenDominion\Http\Controllers\Staff;

use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;
use OpenDominion\Models\UserActivity;

class StaffController extends AbstractController
{
    public function getIndex()
    {
        $staff = User::has('roles')->get();

        return view('pages.staff.index', [
            'staff' => $staff
        ]);
    }

    public function getAudit()
    {
        $resultsPerPage = 25;
        $activities = UserActivity::query()
            ->where('key', 'LIKE', 'staff.audit.%')
            ->orderByDesc('created_at')
            ->paginate($resultsPerPage);

        return view('pages.staff.audit', [
            'activities' => $activities
        ]);
    }
}
