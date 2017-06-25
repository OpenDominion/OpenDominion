<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\DominionSelectorService;

abstract class AbstractDominionController extends AbstractController
{
    /**
     * Returns the logged in user's currently selected dominion.
     *
     * @return Dominion
     */
    protected function getSelectedDominion()
    {
        $dominionSelectorService = app(DominionSelectorService::class);
        return $dominionSelectorService->getUserSelectedDominion();
    }
}
