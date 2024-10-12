<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Models\Dominion;
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

        return redirect()->intended(route('dominion.status'));
    }
}
