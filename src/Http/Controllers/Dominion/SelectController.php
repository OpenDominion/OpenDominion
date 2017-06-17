<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\AnalyticsService;
use OpenDominion\Services\DominionSelectorService;

class SelectController extends AbstractDominionController
{
    public function postSelect(Dominion $dominion)
    {
        $dominionSelectorService = app(DominionSelectorService::class);

        try {
            $dominionSelectorService->selectUserDominion($dominion);

        } catch (Exception $e) {
            return response('Unauthorized', 401);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'dominion',
            'select'
        ));

        return redirect()->route('dominion.status');
    }
}
