<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\AIHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\BankActionService;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\Actions\DestroyActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Traits\DominionGuardsTrait;

class AutomationService
{
    use DominionGuardsTrait;

    public const DAILY_ACTIONS = 3;
    public const MAX_ACTIONS_PER_TICK = 10;
    public const MAX_SCHEDULE_HOURS = 12;
    public const MAX_TEMPLATE_SLOTS = 3;

    public const AUTOMATABLE_ACTIONS = [
        'construct',
        'daily_bonus',
        'draft_rate',
        'explore',
        'release',
        'rezone',
        'spell',
        'train',
    ];

    /** @var BankActionService */
    protected $bankActionService;

    /** @var ChangeDraftRateActionService */
    protected $changeDraftRateActionService;

    /** @var ConstructActionService */
    protected $constructActionService;

    /** @var DailyBonusesActionService */
    protected $dailyBonusesActionService;

    /** @var DestroyActionService */
    protected $destroyActionService;

    /** @var ExploreActionService */
    protected $exploreActionService;

    /** @var ImproveActionService */
    protected $improveActionService;

    /** @var ReleaseActionService */
    protected $releaseActionService;

    /** @var RezoneActionService */
    protected $rezoneActionService;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var TickService */
    protected $tickService;

    /** @var TrainActionService */
    protected $trainActionService;

    protected $lastAction;
    protected $lastHour;

    /**
     * AIService constructor.
     */
    public function __construct()
    {
        // Action Services
        $this->bankActionService = app(BankActionService::class);
        $this->changeDraftRateActionService = app(ChangeDraftRateActionService::class);
        $this->constructActionService = app(ConstructActionService::class);
        $this->dailyBonusesActionService = app(DailyBonusesActionService::class);
        $this->destroyActionService = app(DestroyActionService::class);
        $this->exploreActionService = app(ExploreActionService::class);
        $this->improveActionService = app(ImproveActionService::class);
        $this->releaseActionService = app(ReleaseActionService::class);
        $this->rezoneActionService = app(RezoneActionService::class);
        $this->spellActionService = app(SpellActionService::class);
        $this->tickService = app(TickService::class);
        $this->trainActionService = app(TrainActionService::class);
    }

    public function processLog(Dominion $dominion, array $protection)
    {
        try {
            $currentHour = $dominion->protection_ticks - $dominion->protection_ticks_remaining + 1;

            foreach ($protection as $hour => $actions) {
                // Skip hours that have already been completed
                if ($hour >= $currentHour) {
                    if ($hour == 0) {
                        if (!isset($actions[0])) {
                            continue;
                        }
                        $this->lastAction = $actions[0];
                        $this->lastHour = 0;
                        DB::transaction(function () use ($dominion, $actions) {
                            $dominion->protection_ticks_remaining -= 1;
                            $dominion->save(['event' => HistoryService::EVENT_ACTION_PROTECTION_ADVANCE_TICK]);
                            $this->processStartingBuildings($dominion, $actions[0]['data']);
                        });
                        continue;
                    }
                    if ($dominion->protection_type == 'quick' && !(in_array($dominion->protection_ticks_remaining, [36, 24]) || $dominion->protection_ticks_remaining <= 12)) {
                        // Don't process 'skipped' ticks for Quick Start
                        $actions = [];
                    }
                    if ($dominion->protection_ticks_remaining == 1) {
                        // Check minimum defense is met
                        $this->checkDefense($dominion);
                    }
                    DB::transaction(function () use ($dominion, $hour, $actions) {
                        foreach ($actions as $action) {
                            $this->lastAction = $action;
                            $this->lastHour = $hour;
                            $processFunc = 'process' . ucfirst($action['type']);
                            $this->$processFunc($dominion, $action['data']);
                            $dominion->refresh();
                        }
                        if ($hour < ($dominion->protection_ticks + 1)) {
                            // TODO: De-deplicate from MiscController
                            $dominion->protection_ticks_remaining -= 1;
                            if ($dominion->protection_ticks_remaining == 0) {
                                if ($dominion->created_at < $dominion->round->start_date) {
                                    // Automatically confirm protection finished
                                    $dominion->protection_finished = true;
                                }
                            }
                            if ($dominion->protection_ticks_remaining == 0 ||
                                ($dominion->protection_ticks_remaining == 24 && $dominion->protection_type !== 'quick')
                            ) {
                                // Daily bonuses don't reset during Quick Start
                                if ($dominion->daily_land || $dominion->daily_platinum) {
                                    // Record reset bonuses
                                    $bonusDelta = [];
                                    if ($dominion->daily_land) {
                                        $bonusDelta['daily_land'] = false;
                                    }
                                    if ($dominion->daily_platinum) {
                                        $bonusDelta['daily_platinum'] = false;
                                    }
                                }
                                $dominion->daily_platinum = false;
                                $dominion->daily_land = false;
                                $dominion->daily_actions = static::DAILY_ACTIONS;
                            }
                            $dominion->save(['event' => HistoryService::EVENT_ACTION_PROTECTION_ADVANCE_TICK]);

                            $this->tickService->performTick($dominion->round, $dominion);
                        }
                    });
                }
            }
        } catch (GameException $e) {
            if (!isset($this->lastHour)) {
                $this->lastHour = $dominion->protection_ticks - $dominion->protection_ticks_remaining + 1;
            }
            if (!isset($this->lastAction)) {
                $this->lastAction = ['line' => 0];
            }
            throw new GameException("Error processing hour {$this->lastHour} line {$this->lastAction['line']} - " . $e->getMessage());
        }
    }

    public function processStartingBuildings(Dominion $dominion, array $data)
    {
        $landCalculator = app(LandCalculator::class);
        $totalLand = $landCalculator->getTotalLand($dominion);

        $totalBuildings = array_sum($data);
        if ($totalBuildings !== $totalLand) {
            throw new GameException('Invalid building count.');
        }

        $landHelper = app(LandHelper::class);
        $landTypes = $landHelper->getLandTypes();
        $buildingLandTypes = $landHelper->getLandTypesByBuildingType($dominion->race);

        // Reset land types
        foreach ($landTypes as $landType) {
            $dominion->{"land_$landType"} = 0;
        }

        // Add buildings and the appropriate land type
        foreach ($data as $buildingType => $amount) {
            $landType = $buildingLandTypes[str_replace('building_', '', $buildingType)];
            $dominion->{$buildingType} = $amount;
            $dominion->{"land_{$landType}"} += $amount;
        }

        $populationCalculator = app(PopulationCalculator::class);
        $dominion->peasants = $populationCalculator->getMaxPeasantPopulation($dominion);

        // Remove previous starting_buildings history record
        $latestHistory = $dominion->history()->orderByDesc('created_at')->first();
        if ($latestHistory !== null && isset($latestHistory->delta['action']) && $latestHistory->delta['action'] == 'starting_buildings') {
            $latestHistory->delete();
        }

        $selectedBuildings = array_filter($data, function ($value) {
            return $value !== 0;
        });

        $dominion->save(['event' => HistoryService::EVENT_TICK, 'action' => 'starting_buildings', 'delta' => $selectedBuildings]);
    }

    protected function processBank(Dominion $dominion, array $data)
    {
        foreach ($data as $action) {
            $this->bankActionService->exchange($dominion, $action['source'], $action['target'], $action['amount']);
        }
    }

    protected function processConstruction(Dominion $dominion, array $data)
    {
        $this->constructActionService->construct($dominion, $data);
    }

    protected function processDaily(Dominion $dominion, string $data)
    {
        if ($data == 'platinum') {
            $this->dailyBonusesActionService->claimPlatinum($dominion);
        } else {
            $this->dailyBonusesActionService->claimLand($dominion);
        }
    }

    protected function processDestruction(Dominion $dominion, array $data)
    {
        $this->destroyActionService->destroy($dominion, $data);
    }

    protected function processDraftrate(Dominion $dominion, int $data)
    {
        $this->changeDraftRateActionService->changeDraftRate($dominion, min($data, 90));
    }

    protected function processExplore(Dominion $dominion, array $data)
    {
        $this->exploreActionService->explore($dominion, $data);
    }

    protected function processInvest(Dominion $dominion, array $data) {
        $this->improveActionService->improve($dominion, $data['resource'], [$data['improvement'] => $data['amount']]);
    }

    protected function processMagic(Dominion $dominion, string $data)
    {
        $this->spellActionService->castSpell($dominion, $data);
    }

    protected function processRelease(Dominion $dominion, array $data)
    {
        $this->releaseActionService->release($dominion, $data);
    }

    protected function processRezone(Dominion $dominion, array $data)
    {
        $this->rezoneActionService->rezone($dominion, $data['remove'], $data['add']);
    }

    protected function processTrain(Dominion $dominion, array $data)
    {
        $this->trainActionService->train($dominion, $data);
    }

    public function checkDefense(Dominion $dominion)
    {
        $landCalculator = app(LandCalculator::class);
        $militaryCalculator = app(MilitaryCalculator::class);

        // Queues for next tick
        $incomingQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '=', 1)
            ->get();

        foreach ($incomingQueue as $row) {
            // Temporarily add next hour's resources for accurate calculations
            $dominion->{$row->resource} += $row->amount;
        }

        $totalLand = $landCalculator->getTotalLand($dominion);
        $defensivePower = $militaryCalculator->getDefensivePower($dominion, null, null, null, 0, true, true);
        $minDefense = $militaryCalculator->getMinimumDefense($dominion);

        foreach ($incomingQueue as $row) {
            // Reset current resources
            $dominion->{$row->resource} -= $row->amount;
        }

        if ($defensivePower < $minDefense) {
            throw new GameException(sprintf('You cannot leave protection at this size with less than %s defense.', $minDefense));
        }

        if ($dominion->round->daysInRound() > 1) {
            $aiHelper = app(AIHelper::class);
            $botDefense = round($aiHelper->getDefenseForNonPlayer($dominion->round, $totalLand));
            if ($defensivePower < $botDefense) {
                throw new GameException(sprintf('You cannot leave protection at this size with less than %s defense.', $botDefense));
            }
        }
    }

    public function setConfig(Dominion $dominion, array $data): void
    {
        $config = $dominion->ai_config ?? [];
        if (!isset($config[$data['tick']])) {
            $config[$data['tick']] = [];
        }
        $config[$data['tick']][] = $data['value'];

        $this->saveAutomationConfig($dominion, $config, [(int) $data['tick']]);
    }

    public function deleteAction(Dominion $dominion, int $tick, int $key): void
    {
        $this->guardLockedDominion($dominion);

        $config = $dominion->ai_config ?? [];
        if (!isset($config[$tick][$key])) {
            throw new GameException('Action not found.');
        }

        unset($config[$tick][$key]);
        if (empty($config[$tick])) {
            unset($config[$tick]);
        } else {
            $config[$tick] = array_values($config[$tick]);
        }

        $dominion->ai_config = $config;
        if (empty($config)) {
            $dominion->ai_enabled = false;
        }
        $dominion->save();
    }

    public function reorderAction(Dominion $dominion, int $tick, int $key, string $direction): void
    {
        $this->guardLockedDominion($dominion);

        $config = $dominion->ai_config;
        if (!isset($config[$tick]) || !isset($config[$tick][$key])) {
            throw new GameException('Action not found.');
        }

        $actions = array_values($config[$tick]);
        $swapKey = $direction === 'up' ? $key - 1 : $key + 1;

        if (!isset($actions[$swapKey])) {
            throw new GameException('Cannot move action further in that direction.');
        }

        [$actions[$key], $actions[$swapKey]] = [$actions[$swapKey], $actions[$key]];
        $config[$tick] = $actions;

        $dominion->ai_config = $config;
        $dominion->save();
    }

    public function moveAction(Dominion $dominion, int $tick, int $key, int $targetKey): void
    {
        $this->guardLockedDominion($dominion);

        $config = $dominion->ai_config ?? [];
        if (!isset($config[$tick][$key])) {
            throw new GameException('Action not found.');
        }

        $actions = array_values($config[$tick]);
        if ($targetKey < 0 || $targetKey >= count($actions)) {
            throw new GameException('Invalid action position.');
        }

        if ($key === $targetKey) {
            return;
        }

        $movedActions = array_splice($actions, $key, 1);
        array_splice($actions, $targetKey, 0, $movedActions);
        $config[$tick] = $actions;

        $dominion->ai_config = $config;
        $dominion->save();
    }

    public function editAction(Dominion $dominion, int $tick, int $key, array $value): void
    {
        $this->guardLockedDominion($dominion);

        $config = $dominion->ai_config ?? [];
        if (!isset($config[$tick]) || !isset($config[$tick][$key])) {
            throw new GameException('Action not found.');
        }

        $config[$tick][$key] = $value;
        $this->saveAutomationConfig($dominion, $config);
    }

    public function duplicateAction(Dominion $dominion, int $sourceTick, int $sourceKey, int $targetTick): void
    {
        $this->guardLockedDominion($dominion);

        $config = $dominion->ai_config ?? [];
        if (!isset($config[$sourceTick]) || !isset($config[$sourceTick][$sourceKey])) {
            throw new GameException('Source action not found.');
        }

        $action = $config[$sourceTick][$sourceKey];

        $this->setConfig($dominion, [
            'tick' => $targetTick,
            'value' => $action,
        ]);
    }

    public function copyTick(Dominion $dominion, int $sourceTick, array $targetTicks): void
    {
        $this->guardLockedDominion($dominion);

        $config = $dominion->ai_config ?? [];
        if (!isset($config[$sourceTick]) || empty($config[$sourceTick])) {
            throw new GameException('Source tick not found.');
        }

        $targetTicks = array_values(array_unique(array_map('intval', $targetTicks)));
        if (empty($targetTicks)) {
            throw new GameException('Select at least one target tick.');
        }

        foreach ($targetTicks as $targetTick) {
            if ($targetTick === $sourceTick) {
                throw new GameException('You cannot copy a tick onto itself.');
            }

            $config[$targetTick] = array_merge(
                array_values($config[$targetTick] ?? []),
                array_values($config[$sourceTick])
            );
        }

        $this->saveAutomationConfig($dominion, $config, $targetTicks);
    }

    public function getTemplates(Dominion $dominion): array
    {
        $templates = array_values($dominion->automation_templates ?? []);
        $templates = array_slice($templates, 0, static::MAX_TEMPLATE_SLOTS);

        return array_pad($templates, static::MAX_TEMPLATE_SLOTS, null);
    }

    public function saveTemplate(Dominion $dominion, int $slot, string $name): void
    {
        $this->guardLockedDominion($dominion);
        $this->validateTemplateSlot($slot);

        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 32) {
            throw new GameException('Template names must contain between 1 and 32 characters.');
        }

        $currentTick = $dominion->round->getTick();
        $ticks = [];
        foreach (($dominion->ai_config ?? []) as $tick => $actions) {
            $offset = intval($tick) - $currentTick;
            if ($offset >= 1 && $offset <= static::MAX_SCHEDULE_HOURS && !empty($actions)) {
                $ticks[] = [
                    'offset' => $offset,
                    'actions' => array_values($actions),
                ];
            }
        }

        if (empty($ticks)) {
            throw new GameException('Schedule at least one action before saving a template.');
        }

        $templates = $this->getTemplates($dominion);
        $templates[$slot] = [
            'name' => $name,
            'ticks' => $ticks,
        ];

        $dominion->automation_templates = array_values($templates);
        $dominion->save();
    }

    public function loadTemplate(Dominion $dominion, int $slot, string $mode): void
    {
        $this->validateTemplateSlot($slot);
        if (!in_array($mode, ['replace', 'open'], true)) {
            throw new GameException('Invalid template load behavior.');
        }

        $template = $this->getTemplates($dominion)[$slot];
        if ($template === null) {
            throw new GameException('Template not found.');
        }

        $config = $mode === 'replace' ? [] : ($dominion->ai_config ?? []);
        $currentTick = $dominion->round->getTick();
        $loadedTicks = [];
        foreach ($template['ticks'] as $tick) {
            $targetTick = $currentTick + intval($tick['offset']);
            if ($mode === 'open' && isset($config[$targetTick])) {
                continue;
            }
            $config[$targetTick] = array_values($tick['actions']);
            $loadedTicks[] = $targetTick;
        }

        $this->saveAutomationConfig($dominion, $config, $loadedTicks);
    }

    public function deleteTemplate(Dominion $dominion, int $slot): void
    {
        $this->guardLockedDominion($dominion);
        $this->validateTemplateSlot($slot);

        $templates = $this->getTemplates($dominion);
        if ($templates[$slot] === null) {
            throw new GameException('Template not found.');
        }

        $templates[$slot] = null;
        $dominion->automation_templates = array_values($templates);
        $dominion->save();
    }

    public function clearTick(Dominion $dominion, int $tick): void
    {
        $this->guardLockedDominion($dominion);

        $config = $dominion->ai_config ?? [];
        if (!isset($config[$tick])) {
            throw new GameException('No actions found for this tick.');
        }

        unset($config[$tick]);

        $dominion->ai_config = $config;
        if (empty($config)) {
            $dominion->ai_enabled = false;
        }
        $dominion->save();
    }

    protected function saveAutomationConfig(Dominion $dominion, array $config, array $scheduledTicks = []): void
    {
        $this->guardLockedDominion($dominion);

        if (!$dominion->protection_finished) {
            throw new GameException('You cannot schedule actions while under protection.');
        }

        $currentTick = $dominion->round->getTick();
        $scheduledTicks = array_map('intval', $scheduledTicks);
        $normalizedConfig = [];
        foreach ($config as $tick => $actions) {
            $tick = intval($tick);
            if (in_array($tick, $scheduledTicks, true) && $tick > $currentTick + static::MAX_SCHEDULE_HOURS) {
                throw new GameException('You cannot schedule actions more than 12 hours in advance.');
            }
            if (in_array($tick, $scheduledTicks, true) && $tick <= $currentTick) {
                throw new GameException('You cannot schedule actions for current or past ticks.');
            }
            if (!is_array($actions) || count($actions) > static::MAX_ACTIONS_PER_TICK) {
                throw new GameException('You cannot schedule more than 10 actions in a single hour.');
            }

            $actions = array_values($actions);
            foreach ($actions as $action) {
                if (!is_array($action)) {
                    throw new GameException('Invalid automated action.');
                }

                $this->validateAutomatedAction($action);
            }
            if (!empty($actions)) {
                $normalizedConfig[$tick] = $actions;
            }
        }

        $paidTicks = collect($normalizedConfig)->filter(function (array $actions) {
            return collect($actions)->contains(function (array $action) {
                return $action['action'] !== 'daily_bonus';
            });
        });

        $hoursUntilReset = 24 - $dominion->round->hoursInDay() + 1;
        $beforeResetCount = $paidTicks->filter(function (array $actions, int $tick) use ($currentTick, $hoursUntilReset) {
            return $tick - $currentTick < $hoursUntilReset;
        })->count();
        if ($dominion->daily_actions < $beforeResetCount) {
            throw new GameException('You do not have enough scheduled actions remaining.');
        }

        $afterResetCount = $paidTicks->filter(function (array $actions, int $tick) use ($currentTick, $hoursUntilReset) {
            return $tick - $currentTick >= $hoursUntilReset;
        })->count();
        if ($afterResetCount > max($dominion->daily_actions, static::DAILY_ACTIONS)) {
            throw new GameException('You do not have enough scheduled actions remaining.');
        }

        ksort($normalizedConfig, SORT_NUMERIC);
        $dominion->ai_config = $normalizedConfig;
        $dominion->ai_enabled = !empty($normalizedConfig);
        $dominion->save();
    }

    protected function validateAutomatedAction(array $action): void
    {
        if (!isset($action['action']) || !in_array($action['action'], static::AUTOMATABLE_ACTIONS, true)) {
            throw new GameException('Invalid automated action.');
        }

        if ($action['action'] === 'spell' && in_array($action['key'] ?? null, ['ares_call', 'fools_gold'], true)) {
            throw new GameException('You cannot automate this spell.');
        }
    }

    protected function validateTemplateSlot(int $slot): void
    {
        if ($slot < 0 || $slot >= static::MAX_TEMPLATE_SLOTS) {
            throw new GameException('Invalid template slot.');
        }
    }
}
