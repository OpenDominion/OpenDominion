<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;

class StatusController extends AbstractDominionController
{
    public function getStatus()
    {
        $resultsPerPage = 25;
        $selectedDominion = $this->getSelectedDominion();

        if ($selectedDominion->realm->alignment == 'neutral') {
            $races = Race::all();
        } else {
            $races = Race::where('alignment', $selectedDominion->realm->alignment)->get();
        }
        $notifications = $selectedDominion->notifications()->paginate($resultsPerPage);

        return view('pages.dominion.status', [
            'dominionProtectionService' => app(ProtectionService::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'notificationHelper' => app(NotificationHelper::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'queueService' => app(QueueService::class),
            'races' => $races,
            'notifications' => $notifications
        ]);
    }
}
