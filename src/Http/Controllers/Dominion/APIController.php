<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\API\InvadeCalculationRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\API\InvadeCalculationService;

class APIController extends AbstractDominionController
{
    public function calculateInvasion(InvadeCalculationRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $invadeCalculationService = app(InvadeCalculationService::class);

        try {
            $result = $invadeCalculationService->calculate(
                $dominion,
                Dominion::find($request->get('target_dominion')),
                $request->get('unit')
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }
}
