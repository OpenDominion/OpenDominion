<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\SelectorService;

class SelectController extends AbstractDominionController
{
    public function postSelect(Dominion $dominion)
    {
        $dominionSelectorService = app(SelectorService::class);

        try {
            $dominionSelectorService->selectUserDominion($dominion);

        } catch (Exception $e) {
            // todo: redirect somewhere with error
            return response('Unauthorized', 401);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'select'
        ));

        return redirect()->intended(route('dominion.status'));
    }
}
