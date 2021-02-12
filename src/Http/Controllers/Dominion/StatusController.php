<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\DiscordHelper;
use OpenDominion\Helpers\MiscHelper;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Mappers\Dominion\InfoMapper;
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
            'discordHelper' => app(DiscordHelper::class),
            'infoMapper' => app(InfoMapper::class),
            'landCalculator' => app(LandCalculator::class),
            'miscHelper' => app(MiscHelper::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'notificationHelper' => app(NotificationHelper::class),
            'opsCalculator' => app(OpsCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'queueService' => app(QueueService::class),
            'raceHelper' => app(RaceHelper::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'unitHelper' => app(UnitHelper::class),
            'races' => $races,
            'notifications' => $notifications
        ]);
    }
}
