<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Contracts\Services\Dominion\SelectorService;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;

abstract class AbstractDominionController extends AbstractController
{
    /**
     * Returns the logged in user's currently selected dominion.
     *
     * @return Dominion
     */
    protected function getSelectedDominion()
    {
        $dominionSelectorService = app(SelectorService::class);
        return $dominionSelectorService->getUserSelectedDominion();
    }
}
