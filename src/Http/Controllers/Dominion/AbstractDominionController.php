<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\DominionSelectorService;

abstract class AbstractDominionController extends AbstractController
{
    /**
     * @return Dominion
     */
    protected function getSelectedDominion()
    {
        $dominionSelectorService = resolve(DominionSelectorService::class);
        return $dominionSelectorService->getUserSelectedDominion();
    }
}
