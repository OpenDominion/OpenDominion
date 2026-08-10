<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Http\Requests\Dominion\Actions\CastSpellActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\BountyService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

class MagicController extends AbstractDominionController
{
    public function getMagic(Request $request)
    {
        $targetDominion = $request->input('dominion');

        return view('pages.dominion.magic', [
            'governmentService' => app(GovernmentService::class),
            'guardMembershipService' => app(GuardMembershipService::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'targetDominion' => $targetDominion,
        ]);
    }

    public function postMagic(CastSpellActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $spellActionService = app(SpellActionService::class);
        $fromBountyBoard = $request->boolean('from_bounty_board');

        try {
            $targetDominion = ($request->has('target_dominion') ? Dominion::withGameRelations()->findOrFail($request->get('target_dominion')) : null);

            if ($fromBountyBoard && $targetDominion !== null) {
                app(BountyService::class)->guardActiveBounty($dominion, $targetDominion, $request->get('spell'));
            }

            $result = $spellActionService->castSpell(
                $dominion,
                $request->get('spell'),
                $targetDominion
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);

        $bountyRedirect = $fromBountyBoard ? route('dominion.bounty-board') : null;

        return redirect()
            ->to($bountyRedirect ?? $result['redirect'] ?? route('dominion.magic'))
            ->with('target_dominion', $request->get('target_dominion'));
    }
}
