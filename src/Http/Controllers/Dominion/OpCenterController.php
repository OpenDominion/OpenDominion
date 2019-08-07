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
            ->where('type', '=', 'clairvoyance')
            ->get()
            ->map(static function ($infoOp) {
                return $infoOp->targetRealm;
            })
            ->unique();

        $latestInfoOps = $dominion->realm->infoOps()
            ->with('sourceDominion')
            ->with('targetDominion')
            ->with('targetDominion.race')
            ->with('targetDominion.realm')
            ->where('type', '!=', 'clairvoyance')
            ->where('latest', '=', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('target_dominion_id');

        return view('pages.dominion.op-center.index', [
            'infoOpService' => app(InfoOpService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'latestInfoOps' => $latestInfoOps,
            'clairvoyanceRealms' => $clairvoyanceRealms
        ]);
    }

    public function getDominion(Dominion $dominion)
    {
        $infoOpService = app(InfoOpService::class);

        if (!$infoOpService->hasInfoOps($this->getSelectedDominion()->realm, $dominion)) {
            return redirect()->route('dominion.op-center');
        }

        $latestInfoOps = $this->getSelectedDominion()->realm->infoOps()
            ->with('sourceDominion')
            ->where('target_dominion_id', '=', $dominion->id)
            ->where('latest', '=', true)
            ->get();

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
            'latestInfoOps' => $latestInfoOps
        ]);
    }

    public function getDominionArchive(Dominion $dominion, string $type)
    {
        $resultsPerPage = 10;
        $valid_types = ['clear_sight', 'revelation', 'barracks_spy', 'castle_spy', 'survey_dominion', 'land_spy'];
        $infoOpService = app(InfoOpService::class);

        if (!in_array($type, $valid_types)) {
            return redirect()->route('dominion.op-center.show', $dominion);
        }

        $infoOpArchive = $this->getSelectedDominion()->realm
            ->infoOps()
            ->with('sourceDominion')
            ->where('target_dominion_id', '=', $dominion->id)
            ->where('type', '=', $type)
            ->orderBy('created_at', 'desc')
            ->paginate($resultsPerPage);

        return view('pages.dominion.op-center.archive', [
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
            'infoOpArchive' => $infoOpArchive
        ]);
    }

    public function getClairvoyance(int $realmNumber)
    {
        $infoOpService = app(InfoOpService::class);
        $dominion = $this->getSelectedDominion();

        $targetRealm = Realm::where([
                'round_id' => $dominion->round->id,
                'number' => $realmNumber,
            ])
            ->firstOrFail();

        $clairvoyanceInfoOp = $infoOpService->getInfoOpForRealm(
            $this->getSelectedDominion()->realm,
            $targetRealm,
            'clairvoyance'
        );

        if ($clairvoyanceInfoOp === null) {
            abort(404);
        }

        $gameEventService = app(GameEventService::class);
        $clairvoyanceData = $gameEventService->getClairvoyance($targetRealm, $clairvoyanceInfoOp->created_at);

        $gameEvents = $clairvoyanceData['gameEvents'];
        $dominionIds = $clairvoyanceData['dominionIds'];

        return view('pages.dominion.town-crier', compact(
            'gameEvents',
            'dominionIds',
            'clairvoyanceInfoOp'
        ))->with('realm', $targetRealm)->with('fromOpCenter', true);
    }
}
