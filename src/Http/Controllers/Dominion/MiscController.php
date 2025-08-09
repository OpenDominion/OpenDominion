<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Helpers\AIHelper;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Http\Requests\Dominion\Actions\RestartActionRequest;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\AutomationService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Services\PackService;
use OpenDominion\Traits\DominionGuardsTrait;

// misc functions, probably could use a refactor later
class MiscController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getAbandonDominion(Request $request)
    {
        return view('pages.dominion.abandon');
    }

    public function postAbandonDominion(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        $protectionService = app(ProtectionService::class);

        try {
            $this->guardLockedDominion($dominion);

            if ($protectionService->isUnderProtection($dominion)) {
                throw new GameException('You cannot abandon your dominion while under protection.');
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $dominion->requestAbandonment();
        $dominion->save();

        $request->session()->flash('alert-success', 'Your dominion will be abandoned in 24 hours.');
        return redirect()->route('dominion.misc.abandon');
    }

    public function postCancelAbandonDominion(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        try {
            $this->guardLockedDominion($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $dominion->cancelAbandonment();
        $dominion->save();

        $request->session()->flash('alert-success', 'Your dominion will no longer be abandoned.');
        return redirect()->route('dominion.misc.abandon');
    }

    public function postClearNotifications()
    {
        $this->getSelectedDominion()->notifications->markAsRead();
        return redirect()->back();
    }

    public function postClosePack()
    {
        $dominion = $this->getSelectedDominion();
        $pack = $dominion->pack;

        // Only pack creator can manually close it
        if ($pack->creator_dominion_id !== $dominion->id) {
            return redirect()->back()->withErrors(['Pack may only be closed by the creator.']);
        }

        $pack->close();

        return redirect()->back();
    }

    public function postJoinPack(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        $packService = app(PackService::class);

        if ($dominion->pack_id !== null) {
            return redirect()->back()->withErrors(['You are already a member of a pack.']);
        }

        if ($dominion->round->hasAssignedRealms()) {
            return redirect()->back()->withErrors(['You cannot join a pack after realms have been assigned.']);
        }

        try {
            $pack = $packService->getPack(
                $dominion->round,
                $request->get('pack_name'),
                $request->get('pack_password'),
                $dominion->race
            );
        } catch (GameException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        if ($pack !== null) {
            $dominion->pack_id = $pack->id;
            $dominion->save();

            $request->session()->flash('alert-success', 'You have successfully joined a pack.');
            return redirect()->back();
        } else {
            return redirect()->back()->withErrors(['Pack not found.']);
        }
    }

    public function postReport(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        if ($dominion !== null) {
            $sender = $dominion->name;
        } else {
            $sender = Auth::user()->display_name;
        }

        $type = $request->get('type');
        $message = $request->get('description');

        $webhook = config('app.discord_report_webhook');
        if ($webhook) {
            $client = new Client();
            $response = $client->post($webhook, ['form_params' => [
                'content' => "Report ({$type}) from {$sender}:\n\n{$message}"
            ]]);
        }
        if (!$webhook || $response->getStatusCode() != 204) {
            return redirect()->back()->withErrors(['There was an error submitting your report.']);
        }

        $request->session()->flash('alert-success', 'Your report was submitted successfully.');
        return redirect()->back();
    }

    public function getRestartDominion(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        $dominionFactory = app(DominionFactory::class);
        $protectionService = app(ProtectionService::class);

        try {
            $this->guardLockedDominion($dominion, true);

            if (!$protectionService->isUnderProtection($dominion)) {
                throw new GameException('You can only restart your dominion during protection.');
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        if ($dominion->realm->alignment == 'neutral') {
            $races = Race::where('playable', true)->get()->sortBy('name');
        } else {
            $races = Race::where('playable', true)->where('alignment', $dominion->realm->alignment)->get()->sortBy('name');
        }

        return view('pages.dominion.restart', [
            'races' => $races,
            'quickstarts' => $dominionFactory->getQuickStartOptions()
        ]);
    }

    public function postRenameDominion(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        $protectionService = app(ProtectionService::class);

        $this->validate($request, [
            'dominion_name' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/[a-zA-Z0-9]{3,}/i',
                Rule::unique('dominions', 'name')->where(function ($query) use ($dominion) {
                    return $query->where('round_id', $dominion->round_id);
                })->ignore($dominion->id)
            ],
            'ruler_name' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('dominions', 'ruler_name')->where(function ($query) use ($dominion) {
                    return $query->where('round_id', $dominion->round_id);
                })->ignore($dominion->id)
            ]
        ]);

        try {
            $this->guardLockedDominion($dominion, true);

            if (!$protectionService->isUnderProtection($dominion)) {
                throw new GameException('You can only rename your dominion during protection.');
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        try {
            if ($request->get('dominion_name')) {
                $dominion->name = $request->get('dominion_name');
            }
            if ($request->get('ruler_name')) {
                $dominion->ruler_name = $request->get('ruler_name');
            }
            $dominion->save();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()->withErrors(['There was a problem renaming your dominion.']);
        }

        $request->session()->flash('alert-success', 'Your dominion has been renamed.');
        return redirect()->route('dominion.status');
    }

    public function postRestartDominion(RestartActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();

        $dominionFactory = app(DominionFactory::class);
        $packService = app(PackService::class);
        $protectionService = app(ProtectionService::class);

        // Additional Race validation
        $race = Race::findOrFail($request->get('race'));
        $protectionType = $request->get('protection_type');

        try {
            $this->guardLockedDominion($dominion, true);

            if (!$race->playable) {
                throw new GameException('Invalid race selection');
            }

            if (!in_array($protectionType, ['advanced', 'quick'])) {
                throw new GameException('Invalid start option');
            }

            if (!$protectionService->isUnderProtection($dominion)) {
                throw new GameException('You can only restart your dominion during protection.');
            }

            if ($dominion->realm->alignment !== 'neutral') {
                if ($dominion->realm->alignment !== $race->alignment) {
                    throw new GameException('You cannot change alignment.');
                }
            }

            if ($dominion->pack_id !== null && $dominion->race_id !== $race->id && (int)$dominion->round->players_per_race !== 0) {
                if (!$packService->checkRaceLimitForPack($dominion->pack, $race)) {
                    throw new GameException('Selected race has already been chosen by the maximum number of players in your pack.');
                }

                if (!$packService->checkRaceLimitForRealm($dominion->realm, $race)) {
                    throw new GameException('Selected race has already been chosen by the maximum number of players in your realm.');
                }
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        try {
            $dominionFactory->restart($dominion, $race, $request->get('dominion_name'), $request->get('ruler_name'), $protectionType);
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()->withErrors(['There was a problem restarting your dominion.']);
        }

        $request->session()->flash('alert-success', 'Your dominion has been restarted.');
        return redirect()->route('dominion.status');
    }

    public function getTickDominion(Request $request) {
        $dominion = $this->getSelectedDominion();

        $tickService = app(TickService::class);

        try {
            $this->guardLockedDominion($dominion, true);

            if ($dominion->isBuildingPhase()) {
                $landCalculator = app(LandCalculator::class);
                if ($landCalculator->getTotalBarrenLand($dominion) > 0) {
                    throw new GameException('You have not selected your starting buildings.');
                }

                $dominion->protection_ticks_remaining -= 1;
                $dominion->save(['event' => HistoryService::EVENT_ACTION_PROTECTION_ADVANCE_TICK]);
                return redirect()->back();
            }

            if ($dominion->protection_ticks_remaining == 0) {
                if ($dominion->created_at >= $dominion->round->start_date) {
                    // Confirm protection finished for late start
                    $dominion->protection_finished = true;
                    $dominion->save(['event' => HistoryService::EVENT_ACTION_PROTECTION_ADVANCE_TICK]);
                    return redirect()->back();
                }
                throw new GameException('You have no protection ticks remaining.');
            }

            if ($dominion->last_tick_at > now()->subSeconds(1)) {
                throw new GameException('The Emperor is currently collecting taxes and cannot fulfill your request. Please try again.');
            }

            // Dominions still in protection or newly registered are forced
            // to wait for a short time following OOP to prevent abuse
            if ($dominion->protection_ticks_remaining == 1) {
                $landCalculator = app(LandCalculator::class);
                $militaryCalculator = app(MilitaryCalculator::class);
                $protectionService = app(ProtectionService::class);

                if (!$protectionService->canLeaveProtection($dominion)) {
                    throw new GameException('You cannot leave protection during the first day of the round.');
                }

                // Queues for next tick
                $incomingQueue = DB::table('dominion_queue')
                    ->where('dominion_id', $dominion->id)
                    ->where('hours', '=', 1)
                    ->get();

                foreach ($incomingQueue as $row) {
                    // Temporarily add next hour's resources for accurate calculations
                    $dominion->{$row->resource} += $row->amount;
                }

                $totalLand = $landCalculator->getTotalLand($dominion);
                $defensivePower = $militaryCalculator->getDefensivePower($dominion, null, null, null, 0, false, true);
                $minDefense = $militaryCalculator->getMinimumDefense($dominion);

                foreach ($incomingQueue as $row) {
                    // Reset current resources
                    $dominion->{$row->resource} -= $row->amount;
                }

                if ($defensivePower < $minDefense) {
                    throw new GameException('You cannot leave protection with less than the minimum defense.');
                }

                if ($dominion->round->daysInRound() > 1) {
                    $aiHelper = app(AIHelper::class);
                    $botDefense = round($aiHelper->getDefenseForNonPlayer($dominion->round, $totalLand));
                    if ($defensivePower < $botDefense) {
                        throw new GameException(sprintf('You cannot leave protection at this size with less than %s defense.', $botDefense));
                    }
                }
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $ticksToAdvance = 1;
        if ($dominion->protection_type == 'quick') {
            if (in_array($dominion->protection_ticks_remaining, [36, 24])) {
                // Move ahead 12 ticks instead of 1
                $ticksToAdvance = 12;
            }
        }

        foreach (range(1, $ticksToAdvance) as $tick) {
            $dominion->protection_ticks_remaining -= 1;

            // Queue late start defense at 12 hours remaining
            if ($dominion->protection_ticks_remaining == 12 && $dominion->round->daysInRound() > 1) {
                $this->queueLateStartDefense($dominion);
            }

            if ($dominion->protection_ticks_remaining == 0) {
                if ($dominion->created_at < $dominion->round->start_date) {
                    // Automatically confirm protection finished
                    $dominion->protection_finished = true;
                }
            }

            if ($dominion->protection_ticks_remaining == 0 ||
                ($dominion->protection_ticks_remaining == 24 && $dominion->protection_type !== 'quick')
            ) {
                // Daily bonuses don't reset during Quick Start
                $dominion->daily_platinum = false;
                $dominion->daily_land = false;
                $dominion->daily_actions = AutomationService::DAILY_ACTIONS;
            }
            $dominion->save(['event' => HistoryService::EVENT_ACTION_PROTECTION_ADVANCE_TICK]);
            $tickService->performTick($dominion->round, $dominion);
        }

        return redirect()->back();
    }

    public function getUndoTickDominion(Request $request) {
        $dominion = $this->getSelectedDominion();

        $protectionService = app(ProtectionService::class);
        $tickService = app(TickService::class);

        try {
            $this->guardLockedDominion($dominion);

            if (!$protectionService->isUnderProtection($dominion)) {
                throw new GameException('You cannot undo a tick outside of protection.');
            }

            if ($dominion->last_tick_at > now()->subSeconds(1)) {
                throw new GameException('The Emperor is currently collecting taxes and cannot fulfill your request. Please try again.');
            }

            if ($dominion->protection_ticks_remaining == ($dominion->protection_ticks + 1)) {
                throw new GameException('You have no ticks left to undo.');
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $ticksToRevert = 1;
        if ($dominion->protection_type == 'quick' && in_array($dominion->protection_ticks_remaining, [24, 12])) {
            // Revert 12 ticks instead of 1
            $ticksToRevert = 12;
        }
        foreach (range(1, $ticksToRevert) as $tick) {
            $isReverted = $tickService->revertTick($dominion);
        }
        if (!$isReverted) {
            $request->session()->flash('alert-danger', 'There are no actions to undo.');
        }

        return redirect()->back();
    }

    public function getDominionSettings(Request $request) {
        $dominion = $this->getSelectedDominion();

        try {
            $this->guardLockedDominion($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.settings', [
            'rankingsHelper' => app(RankingsHelper::class),
        ]);
    }

    public function postDominionSettings(Request $request) {
        $dominion = $this->getSelectedDominion();

        try {
            $this->guardLockedDominion($dominion);

            $settings = $dominion->settings;
            $settings['preferred_title'] = $request->get('title');
            $settings['show_icon'] = $request->get('show_icon');
            $settings['black_guard_icon'] = $request->get('black_guard_icon');

            $dominion->settings = $settings;
            $dominion->save();
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your settings have been updated!');
        return redirect()->route('dominion.misc.settings');
    }

    /**
     * Queue late start defense bonus for eligible dominions.
     *
     * @param Dominion $dominion
     */
    protected function queueLateStartDefense($dominion): void
    {
        $race = $dominion->race;
        $aiHelper = app(AIHelper::class);
        $landCalculator = app(LandCalculator::class);
        $militaryCalculator = app(MilitaryCalculator::class);
        $queueService = app(\OpenDominion\Services\Dominion\QueueService::class);

        // Determine unit type based on race
        if ($race->name == 'Goblin') {
            $unitSlot = 2;
        } elseif ($race->name == 'Troll') {
            $unitSlot = 4;
        } else {
            $unitSlot = 3;
        }

        $totalLand = $landCalculator->getTotalLand($dominion);
        $botDefense = $aiHelper->getDefenseForNonPlayer($dominion->round, $totalLand);
        $currentDefense = $militaryCalculator->getDefensivePower($dominion, null, null, null, 0, true, false);
        $defenseMod = $militaryCalculator->getDefensivePowerMultiplier($dominion);
        $unitPower = $militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $race->units[$unitSlot - 1], 'defense');

        $defenseNeeded = ($botDefense - $currentDefense) / $defenseMod * 1.1;

        if ($defenseNeeded > 0) {
            $unitsNeeded = round($defenseNeeded / $unitPower);

            // Queue the units for 12 hours (when protection ends)
            $queueService->queueResources(
                'training',
                $dominion,
                ["military_unit{$unitSlot}" => $unitsNeeded],
                13
            );
        }
    }
}
