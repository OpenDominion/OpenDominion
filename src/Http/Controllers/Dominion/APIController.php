<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Http\Requests\Dominion\API\InvadeCalculationRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\API\InvadeCalculationService;
use Throwable;

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
        } catch (Throwable $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }
}
