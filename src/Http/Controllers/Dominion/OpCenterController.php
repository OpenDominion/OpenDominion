<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\InfoOpService;
use OpenDominion\Services\GameEventService;

class OpCenterController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        $clairvoyanceRealms = $dominion->realm->infoOps()
            ->where('type', 'clairvoyance')
            ->get()
            ->map(static function ($infoOp) {
                return $infoOp->targetRealm;
            });

        return view('pages.dominion.op-center.index', [
            'infoOpService' => app(InfoOpService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'targetDominions' => $dominion->realm->infoOpTargetDominions,
            'clairvoyanceRealms' => $clairvoyanceRealms
        ]);
    }

    public function getDominion(Dominion $dominion)
    {
        $infoOpService = app(InfoOpService::class);

        if (!$infoOpService->hasInfoOps($this->getSelectedDominion()->realm, $dominion)) {
            return redirect()->route('dominion.op-center');
        }

        return view('pages.dominion.op-center.show', [
            'buildingHelper' => app(BuildingHelper::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'infoOpService' => app(InfoOpService::class),
            'landCalculator' => app(LandCalculator::class),
            'landHelper' => app(LandHelper::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'dominion' => $dominion,
        ]);
    }

    public function getClairvoyance(int $realmNumber)
    {
        $infoOpService = app(InfoOpService::class);
        $targetRealm = Realm::findOrFail($realmNumber);

        $clairvoyanceInfoOp = $infoOpService->getInfoOpForRealm(
            $this->getSelectedDominion()->realm,
            $targetRealm,
            'clairvoyance'
        );

        if ($clairvoyanceInfoOp === null) {
            abort(404);
        }

        $gameEventService = app(GameEventService::class);
        $clairvoyanceData = $gameEventService->getClairvoyance($targetRealm, $clairvoyanceInfoOp->updated_at);

        $gameEvents = $clairvoyanceData['gameEvents'];
        $dominionIds = $clairvoyanceData['dominionIds'];

        return view('pages.dominion.town-crier', compact(
            'gameEvents',
            'dominionIds',
            'clairvoyanceInfoOp'
        ))->with('realm', $targetRealm)->with('fromOpCenter', true);
    }
}
