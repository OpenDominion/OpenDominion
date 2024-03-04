<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\InfoHelper;
use OpenDominion\Http\Requests\Dominion\Actions\BountyActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\BountyService;
use OpenDominion\Traits\DominionGuardsTrait;

class BountyController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getBountyBoard(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        $bountyService = app(BountyService::class);
        $bounties = $bountyService->getBounties($dominion->realm);
        $bountiesCollected = $bountyService->getBountiesCollected($dominion);

        return view('pages.dominion.bounty-board', [
            'bountiesActive' => $bounties->where('active', true),
            'bountiesInactive' => $bounties->where('active', false),
            'bountiesCollected' => $bountiesCollected,
            'bountyService' => $bountyService,
            'landCalculator' => app(LandCalculator::class),
            'rangeCalculator' => app(RangeCalculator::class)
        ]);
    }

    public function getCreateBounty(BountyActionRequest $request, int $target, string $type)
    {
        $dominion = $this->getSelectedDominion();
        $bountyService = app(BountyService::class);

        try {
            $target = Dominion::findOrFail($target);
            $this->guardLockedDominion($dominion);
            $this->guardLockedDominion($target);
            $result = $bountyService->createBounty($dominion, $target, $type);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-' . ($result['alert-type'] ?? 'success'), $result['message']);
        return redirect()->back();
    }

    public function getDeleteBounty(BountyActionRequest $request, int $target, string $type)
    {
        $dominion = $this->getSelectedDominion();
        $bountyService = app(BountyService::class);

        try {
            $target = Dominion::findOrFail($target);
            $this->guardLockedDominion($dominion);
            $result = $bountyService->deleteBounty($dominion, $target, $type);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-' . ($result['alert-type'] ?? 'success'), $result['message']);
        return redirect()->back();
    }
}
