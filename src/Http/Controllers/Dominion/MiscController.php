<?php

namespace OpenDominion\Http\Controllers\Dominion;

use LogicException;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Http\Requests\Dominion\Actions\RestartActionRequest;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\TickService;

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
            throw new LogicException('Pack may only be closed by the creator');
        }

        $pack->closed_at = now();
        $pack->save();

        return redirect()->back();
    }

    public function postRestartDominion(RestartActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();

        $dominionFactory = app(DominionFactory::class);
        $protectionService = app(ProtectionService::class);
        $race = Race::findOrFail($request->get('race'));

        if (!$protectionService->isUnderProtection($dominion)) {
            throw new LogicException('You can only restart your dominion during protection.');
        }

        if ($dominion->realm->alignment !== 'neutral') {
            if ($dominion->realm->alignment !== $race->alignment) {
                throw new LogicException('You cannot change alignment.');
            }
        }

        $dominionFactory->restart($dominion, $race);

        return redirect()->back();
    }

    public function getTickDominion() {
        $dominion = $this->getSelectedDominion();

        $protectionService = app(ProtectionService::class);
        $tickService = app(TickService::class);

        if ($dominion->protection_ticks_remaining == 0) {
            throw new LogicException('You have no protection ticks remaining.');
        }

        // Dominions still in protection or newly registered are forced
        // to wait for a short time following OOP to preven abuse
        if (!$protectionService->canLeaveProtection($dominion)) {
            throw new LogicException('You cannot leave protection at this time.');
        }

        $tickService->performTick($dominion->round, $dominion);

        $dominion->protection_ticks_remaining -= 1;
        if ($dominion->protection_ticks_remaining == 48 || $dominion->protection_ticks_remaining == 24) {
            $dominion->daily_platinum = false;
            $dominion->daily_land = false;
        }
        $dominion->save();

        return redirect()->back();
    }
}
