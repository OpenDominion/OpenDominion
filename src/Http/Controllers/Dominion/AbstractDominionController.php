<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\SelectorService;

abstract class AbstractDominionController extends AbstractController
{
    /**
     * Returns the logged in user's currently selected dominion.
     *
     * @return Dominion
     */
    protected function getSelectedDominion(): Dominion
    {
        $dominionSelectorService = app(SelectorService::class);
        return $dominionSelectorService->getUserSelectedDominion();
    }
}
