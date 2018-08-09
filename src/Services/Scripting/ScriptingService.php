<?php

namespace OpenDominion\Services\Scripting;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\BankActionService;
use OpenDominion\Services\Dominion\Actions\DestroyActionService;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;

class ScriptingService 
{
    /** @var BankActionService */
    protected $bankActionService;

    /** @var DestroyActionService */
    protected $destroyActionService;

    /** @var ConstructActionService */
    protected $constructActionService;

    /** @var DailyBonusesActionService */
    protected $dailyBonusesActionService;

    /** @var ExploreActionService */
    protected $exploreActionService;

    /** @var ReleaseActionService */
    protected $releaseActionService;

    /** @var RezoneActionService */
    protected $rezoneActionService;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var TrainActionService */
    protected $trainActionService;

    public function __construct()
    {
        $this->bankActionService = app(BankActionService::class);
        $this->destroyActionService = app(DestroyActionService::class);
        $this->constructActionService = app(ConstructActionService::class);
        $this->dailyBonusesActionService = app(DailyBonusesActionService::class);
        $this->exploreActionService = app(ExploreActionService::class);
        $this->releaseActionService = app(ReleaseActionService::class);
        $this->rezoneActionService = app(RezoneActionService::class);
        $this->spellActionService = app(SpellActionService::class);
        $this->trainActionService = app(TrainActionService::class);
    }

    public function scriptHour(Dominion $dominion, array $data): array
    {
        foreach($data as $type => $typeData)
        {
            $func = "perform{$type}";

            $results[$type] = $this->$func($dominion, $typeData);
        }

        return $results;
    }

    function performDaily(Dominion $dominion, array $data): array
    {
        // TODO: Land bonus needs to be taken in the correct order
        if(in_array('plat', $data))
        {
            $results[] = $this->dailyBonusesActionService->claimPlatinum($dominion);
        }

        if(in_array('land', $data))
        {
            $results[] = $this->dailyBonusesActionService->claimLand($dominion);
        }

        return $results;
    }

    function performBank(Dominion $dominion, array $data): array
    {
        return $this->bankActionService->exchange($dominion, $data['source'], $data['target'], $data['amount']);
    }

    function performDestruction(Dominion $dominion, array $data): array
    {
        return $this->destroyActionService->destroy($dominion, $data);
    }

    function performRezone(Dominion $dominion, array $data): array
    {
        return $this->rezoneActionService->rezone($dominion, $data['remove'], $data['add']);
    }

    function performConstruction(Dominion $dominion, array $data): array
    {
        return $this->constructActionService->construct($dominion, $data);
    }

    function performExplore(Dominion $dominion, array $data): array
    {
        return $this->exploreActionService->explore($dominion, $data);
    }

    function performMagic(Dominion $dominion, array $data): array
    {
        foreach($data as $spell)
        {
            $results[] = $this->spellActionService->castSpell($dominion, $spell);
        }

        return $results;
    }

    function performTrain(Dominion $dominion, array $data): array
    {
        return $this->trainActionService->train($dominion, $data);
    }

    function performRelease(Dominion $dominion, array $data): array
    {
        return $this->releaseActionService->release($dominion, $data);
    }
}