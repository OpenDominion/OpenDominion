<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\MiscHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\InfoOpService;
use OpenDominion\Services\GameEventService;

class OpCenterController extends AbstractDominionController
{
    /**
     * @var GameEventService
     */
    private $gameEventService;

    public function __construct(GameEventService $gameEventService)
    {
        $this->gameEventService = $gameEventService;
    }

    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();

        if ($dominion->locked_at !== null) {
            return redirect()->back()->withErrors(['Locked dominions are not allowed access to the op center.']);
        }

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

        $clairvoyances = $dominion->realm->infoOps()
            ->with('sourceDominion')
            ->with('targetDominion')
            ->with('targetRealm')
            ->where('type', '=', 'clairvoyance')
            ->where('latest', '=', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.dominion.op-center.index', [
            'infoOpService' => app(InfoOpService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'latestInfoOps' => $latestInfoOps,
            'clairvoyances' => $clairvoyances
        ]);
    }

    public function getDominion(Dominion $dominion)
    {
        $selectedDominion = $this->getSelectedDominion();

        if ($selectedDominion->locked_at !== null) {
            return redirect()->back()->withErrors(['Locked dominions are not allowed access to the op center.']);
        }

        if ($selectedDominion->round_id != $dominion->round_id) {
            return redirect()->route('dominion.op-center');
        }

        if ($dominion->pack_id !== null && $selectedDominion->pack_id == $dominion->pack_id) {
            return redirect()->route('dominion.realm.advisors.production', $dominion);
        }

        $latestInfoOps = $this->getSelectedDominion()->realm->infoOps()
            ->with('sourceDominion')
            ->where('target_dominion_id', '=', $dominion->id)
            ->where('latest', '=', true)
            ->get();

        $latestInvasionEvents = $this->gameEventService->getLatestInvasionEventsForDominion($dominion, 4);

        return view('pages.dominion.op-center.show', [
            'buildingHelper' => app(BuildingHelper::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'infoOpService' => app(InfoOpService::class),
            'landCalculator' => app(LandCalculator::class),
            'landHelper' => app(LandHelper::class),
            'miscHelper' => app(MiscHelper::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'techHelper' => app(TechHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'dominion' => $dominion,
            'latestInfoOps' => $latestInfoOps,
            'latestInvasionEvents' => $latestInvasionEvents,
        ]);
    }

    public function getDominionArchive(Dominion $dominion, string $type)
    {
        $selectedDominion = $this->getSelectedDominion();

        if ($selectedDominion->locked_at !== null) {
            return redirect()->back()->withErrors(['Locked dominions are not allowed access to the op center.']);
        }

        if ($selectedDominion->round_id != $dominion->round_id) {
            return redirect()->route('dominion.op-center');
        }

        $resultsPerPage = 10;
        $valid_types = ['clear_sight', 'vision', 'revelation', 'barracks_spy', 'castle_spy', 'survey_dominion', 'land_spy'];

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
            'techHelper' => app(TechHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'miscHelper' => app(MiscHelper::class),
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
