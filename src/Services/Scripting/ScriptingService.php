<?php

namespace OpenDominion\Services\Scripting;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions;

class ScriptingService 
{
    /** @var ExploreActionService */
    protected $exploreActionService;

    public function __construct()
    {
        $this->exploreActionService = app(\OpenDominion\Services\Dominion\Actions\ExploreActionService::class);
    }

    public function scriptHour(Dominion $dominion, array $data): array
    {
        // $types = array(
        // 'daily',
        // 'bank',
        // 'destruction',
        // 'rezone',
        // 'construction',
        // 'explore',
        // 'magic',
        // 'train',
        // 'release');
        foreach($data as $type => $typeData)
        {
            $func = "perform{$type}";
            
            $results[] = $this->$func($dominion, $typeData);
        }

        return $results;
    }

    function performExplore(Dominion $dominion, array $exploreData): array
    {
        return $this->exploreActionService->explore($dominion, $exploreData);
    }
}