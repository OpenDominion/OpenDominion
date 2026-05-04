<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\ValuablesHelper;
use OpenDominion\Models\Valuable;
use OpenDominion\Services\Dominion\Actions\ValuablesActionService;

class ValuablesController extends AbstractDominionController
{
    public function getInvestigate(Valuable $valuable)
    {
        $dominion = $this->getSelectedDominion();

        if ($valuable->source_dominion_id !== $dominion->id) {
            abort(404);
        }

        $valuablesHelper = app(ValuablesHelper::class);
        $militaryCalculator = app(MilitaryCalculator::class);
        $landCalculator = app(\OpenDominion\Calculators\Dominion\LandCalculator::class);

        $targetLand = $landCalculator->getTotalLand($valuable->targetDominion);
        $config = ValuablesHelper::getRarityConfig()[$valuable->rarity];
        $requiredSpyHours = (int) ceil($targetLand * $config['spy_hours_multiplier']);

        $availableSpies = $valuablesHelper->getAvailableSpies($dominion);
        $currentRegen = $militaryCalculator->getSpyStrengthRegen($dominion);
        $activeInvestigations = Valuable::query()
            ->where('source_dominion_id', $dominion->id)
            ->where('status', Valuable::STATUS_INVESTIGATING)
            ->count();

        $minSpies = (int) ceil($requiredSpyHours / ValuablesHelper::MIN_INVESTIGATION_HOURS);
        $maxSpies = (int) ceil($requiredSpyHours / ValuablesHelper::MAX_INVESTIGATION_HOURS);

        $durationOptions = [];
        for (
            $hours = ValuablesHelper::MAX_INVESTIGATION_HOURS;
            $hours <= ValuablesHelper::MIN_INVESTIGATION_HOURS;
            $hours += ValuablesHelper::INVESTIGATION_HOUR_STEP
        ) {
            $spiesNeeded = (int) ceil($requiredSpyHours / $hours);
            $totalStrengthCost = $hours * ValuablesHelper::SPY_STRENGTH_PER_INVESTIGATION;
            $projectedRegen = $currentRegen - ValuablesHelper::SPY_STRENGTH_PER_INVESTIGATION;

            $disabled = false;
            $disabledReason = null;

            if ($spiesNeeded < $minSpies || $spiesNeeded > $maxSpies) {
                $disabled = true;
                $disabledReason = 'Outside the allowed bounds for this valuable.';
            } elseif ($spiesNeeded > $availableSpies) {
                $disabled = true;
                $disabledReason = sprintf('You only have %s available spies.', number_format($availableSpies));
            } elseif ($projectedRegen <= 0) {
                $disabled = true;
                $disabledReason = 'Would prevent your spy strength from regenerating.';
            }

            $durationOptions[] = [
                'hours' => $hours,
                'spiesNeeded' => $spiesNeeded,
                'totalStrengthCost' => $totalStrengthCost,
                'completesAt' => now()->copy()->startOfHour()->addHour()->addHours($hours),
                'disabled' => $disabled,
                'disabledReason' => $disabledReason,
            ];
        }

        return view('pages.dominion.valuables.investigate', [
            'valuable' => $valuable,
            'durationOptions' => $durationOptions,
            'requiredSpyHours' => $requiredSpyHours,
            'availableSpies' => $availableSpies,
            'currentRegen' => $currentRegen,
            'activeInvestigations' => $activeInvestigations,
            'valuablesHelper' => $valuablesHelper,
        ]);
    }

    public function postInvestigate(Request $request, Valuable $valuable)
    {
        $dominion = $this->getSelectedDominion();
        $service = app(ValuablesActionService::class);
        $hours = (int) $request->input('hours');

        try {
            $result = $service->startInvestigation($dominion, $valuable, $hours);
        } catch (GameException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-' . ($result['alert-type'] ?? 'success'), $result['message']);
        return redirect()->route('dominion.espionage');
    }

    public function postCancel(Request $request, Valuable $valuable)
    {
        $dominion = $this->getSelectedDominion();
        $service = app(ValuablesActionService::class);

        try {
            $result = $service->cancelInvestigation($dominion, $valuable);
        } catch (GameException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-' . ($result['alert-type'] ?? 'success'), $result['message']);
        return redirect()->route('dominion.espionage');
    }

    public function postSell(Request $request, Valuable $valuable)
    {
        $dominion = $this->getSelectedDominion();
        $service = app(ValuablesActionService::class);

        try {
            $result = $service->sellValuable($dominion, $valuable);
        } catch (GameException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-' . ($result['alert-type'] ?? 'success'), $result['message']);
        return redirect()->route('dominion.espionage');
    }

    public function postList(Request $request, Valuable $valuable)
    {
        $dominion = $this->getSelectedDominion();
        $service = app(ValuablesActionService::class);

        try {
            $result = $service->listValuable($dominion, $valuable);
        } catch (GameException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-' . ($result['alert-type'] ?? 'success'), $result['message']);
        return redirect()->back();
    }

    public function postUnlist(Request $request, Valuable $valuable)
    {
        $dominion = $this->getSelectedDominion();
        $service = app(ValuablesActionService::class);

        try {
            $result = $service->unlistValuable($dominion, $valuable);
        } catch (GameException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-' . ($result['alert-type'] ?? 'success'), $result['message']);
        return redirect()->back();
    }

    public function postPurchase(Request $request, Valuable $valuable)
    {
        $dominion = $this->getSelectedDominion();
        $service = app(ValuablesActionService::class);

        try {
            $result = $service->purchaseValuable($dominion, $valuable);
        } catch (GameException $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-' . ($result['alert-type'] ?? 'success'), $result['message']);
        return redirect()->route('dominion.bounty-board');
    }

    public function getHistory()
    {
        $dominion = $this->getSelectedDominion();

        $history = Valuable::query()
            ->where('source_dominion_id', $dominion->id)
            ->where('round_id', $dominion->round_id)
            ->whereIn('status', [
                Valuable::STATUS_SOLD,
                Valuable::STATUS_EXPIRED,
                Valuable::STATUS_FAILED,
            ])
            ->with('targetDominion')
            ->orderByDesc('updated_at')
            ->get();

        $totalAttempts = $history->count();
        $successfulThefts = $history->where('status', Valuable::STATUS_SOLD)->count();
        $failedThefts = $history->where('status', Valuable::STATUS_FAILED)->count();
        $expired = $history->where('status', Valuable::STATUS_EXPIRED)->count();
        $totalPlatinumEarned = (int) $history->where('status', Valuable::STATUS_SOLD)->sum('sold_price');
        $successRate = $totalAttempts > 0 ? ($successfulThefts / $totalAttempts) * 100 : 0;

        return view('pages.dominion.valuables.history', [
            'history' => $history,
            'stats' => [
                'totalAttempts' => $totalAttempts,
                'successfulThefts' => $successfulThefts,
                'failedThefts' => $failedThefts,
                'sold' => $successfulThefts,
                'expired' => $expired,
                'totalPlatinumEarned' => $totalPlatinumEarned,
                'successRate' => $successRate,
            ],
        ]);
    }
}
