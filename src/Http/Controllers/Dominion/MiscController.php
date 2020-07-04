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
use OpenDominion\Http\Requests\Dominion\Actions\RestartActionRequest;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Services\PackService;

// misc functions, probably could use a refactor later
class MiscController extends AbstractDominionController
{
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
            throw new GameException('Pack may only be closed by the creator');
        }

        $pack->closed_at = now();
        $pack->save();

        return redirect()->back();
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
                'content' => "Report ({$type}) from {$sender}\n\n{$message}:"
            ]]);
        }
        if (!$webhook || $response->getStatusCode() != 204) {
            return redirect()->back()->withErrors(['There was an error submitting your report.']);
        }

        $request->session()->flash('alert-success', 'Your report was submitted successfully.');
        return redirect()->back();
    }

    public function postRestartDominion(RestartActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();

        $dominionFactory = app(DominionFactory::class);
        $packService = app(PackService::class);
        $protectionService = app(ProtectionService::class);

        $this->validate($request, [
            'race' => 'required|exists:races,id',
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

        // Additional Race validation
        $race = Race::findOrFail($request->get('race'));
        try {
            if (!$race->playable) {
                throw new GameException('Invalid race selection');
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
            $dominionFactory->restart($dominion, $race, $request->get('dominion_name'), $request->get('ruler_name'));
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()->withErrors(['There was a problem restarting your account.']);
        }

        return redirect()->back();
    }

    public function getTickDominion(Request $request) {
        $dominion = $this->getSelectedDominion();

        $tickService = app(TickService::class);

        try {
            if ($dominion->protection_ticks_remaining == 0) {
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
                    throw new GameException('You cannot leave protection during the fourth day of the round.');
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
                $minimumDefense = $militaryCalculator->getMinimumDefense($dominion);
                $defensivePower = $militaryCalculator->getDefensivePower($dominion);

                foreach ($incomingQueue as $row) {
                    // Reset current resources
                    $dominion->{$row->resource} += $row->amount;
                }

                if ($totalLand > 600 && $defensivePower <= $minimumDefense) {
                    throw new GameException('You cannot leave protection at this size with less than minimum defense. 5 * (Land - 150)');
                }
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $tickService->performTick($dominion->round, $dominion);

        $dominion->protection_ticks_remaining -= 1;
        if ($dominion->protection_ticks_remaining == 48 || $dominion->protection_ticks_remaining == 24 || $dominion->protection_ticks_remaining == 0) {
            $dominion->daily_platinum = false;
            $dominion->daily_land = false;
        }
        $dominion->save();

        return redirect()->back();
    }
}
