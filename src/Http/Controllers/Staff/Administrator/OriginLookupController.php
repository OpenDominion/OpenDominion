<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Http\Requests\Staff\Administrator\PerformRoundOriginLookupsRequest;
use OpenDominion\Models\Round;
use OpenDominion\Services\Activity\OriginLookupBatchService;
use OpenDominion\Services\Activity\OriginLookupReportService;
use OpenDominion\Services\Activity\OriginLookupSelectionService;

class OriginLookupController extends AbstractController
{
    /**
     * Session key holding the most recent lookup run report.
     */
    public const REPORT_SESSION_KEY = 'origin-lookup-report';

    public function getIndex(Request $request): View
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        $originLookupSelectionService = app(OriginLookupSelectionService::class);
        $availableTierCounts = $originLookupSelectionService->countAvailableByTier($round);

        $selectedTier = $request->input('tier');
        if (!in_array($selectedTier, OriginLookupSelectionService::TIERS, true) || $availableTierCounts[$selectedTier] === 0) {
            $selectedTier = collect($availableTierCounts)->filter()->keys()->first() ?? OriginLookupSelectionService::TIER_SHARED;
        }

        return view('pages.staff.administrator.ip-lookups.index', [
            'round' => $round,
            'rounds' => $rounds,
            'availableTierCounts' => $availableTierCounts,
            'selectedTier' => $selectedTier,
            'unlookedIpCount' => $originLookupSelectionService->countUnlookedIpAddresses($round),
            'lookupsEnabled' => (bool) config('app.ipqs_api_key'),
            'roundReport' => app(OriginLookupReportService::class)->getRoundReport($round),
        ]);
    }

    public function postIndex(PerformRoundOriginLookupsRequest $request): RedirectResponse
    {
        $round = Round::findOrFail($request->input('round'));
        $tier = $request->input('tier');
        $indexRedirect = redirect()->route('staff.administrator.ip-lookups', ['round' => $round->id, 'tier' => $tier]);

        if (!config('app.ipqs_api_key')) {
            $request->session()->flash('alert-danger', 'IP lookups are unavailable because no IPQS API key is configured.');

            return $indexRedirect;
        }

        $report = app(OriginLookupBatchService::class)->run($round, $tier);

        if ($report['selected'] === 0) {
            $request->session()->flash('alert-info', "No IPs are left to look up in this tier for {$round->name}.");

            return $indexRedirect;
        }

        $request->session()->put(self::REPORT_SESSION_KEY, $report);

        return redirect()->route('staff.administrator.ip-lookups.report');
    }

    public function getReport(Request $request): View|RedirectResponse
    {
        $report = $request->session()->get(self::REPORT_SESSION_KEY);

        if ($report === null) {
            $request->session()->flash('alert-info', 'There is no lookup run to report on yet.');

            return redirect()->route('staff.administrator.ip-lookups');
        }

        return view('pages.staff.administrator.ip-lookups.report', [
            'report' => $report,
            'lookupsEnabled' => (bool) config('app.ipqs_api_key'),
        ]);
    }
}
