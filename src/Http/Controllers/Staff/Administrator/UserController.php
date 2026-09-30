<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Http\Requests\Staff\Administrator\PerformOriginLookupRequest;
use OpenDominion\Models\User;
use OpenDominion\Services\Activity\ActivityService;

class UserController extends AbstractController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('display_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->paginate(100)
            ->withQueryString();

        return view('pages.staff.administrator.users.index', [
            'search' => $search,
            'users' => $users,
        ]);
    }

    public function show(User $user): View
    {
        $origins = $user->origins()
            ->with(['dominion.round', 'lookup'])
            ->orderByDesc('updated_at')
            ->get();

        return view('pages.staff.administrator.users.show', [
            'user' => $user,
            'origins' => $origins,
            'lookupsEnabled' => (bool) config('app.ipqs_api_key'),
        ]);
    }

    public function performOriginLookup(PerformOriginLookupRequest $request, User $user): RedirectResponse
    {
        $ipAddress = $request->input('ip_address');

        if (!config('app.ipqs_api_key')) {
            $request->session()->flash('alert-danger', 'IP lookups are unavailable because no IPQS API key is configured.');

            return redirect()->route('staff.administrator.users.show', $user);
        }

        if (app(ActivityService::class)->performLookup($user, $ipAddress)) {
            $request->session()->flash('alert-success', "Lookup completed for {$ipAddress}.");
        } else {
            $request->session()->flash('alert-danger', "Lookup failed for {$ipAddress}. Check the application log for details.");
        }

        return redirect()->route('staff.administrator.users.show', $user);
    }

    public function takeOver(User $user)
    {
        auth()->login($user);

        return redirect()->route('dashboard');
    }
}
