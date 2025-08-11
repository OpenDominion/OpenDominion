<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\RaidHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InvasionService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Traits\DominionGuardsTrait;

class RaidActionService
{
    use DominionGuardsTrait;

    /** @var InvasionService */
    protected $invasionService;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var RaidCalculator */
    protected $raidCalculator;

    /** @var RaidHelper */
    protected $raidHelper;

    /** @var array Attack result array. todo: Should probably be refactored later to its own class */
    protected $attackResult = [
        'attacker' => [
            'unitsLost' => [],
            'unitsSent' => [],
            'damage' => 0,
            'op' => 0,
        ]
    ];

    // todo: refactor
    /** @var GameEvent */
    protected $attackEvent;

    /**
     * RaidActionService constructor.
     */
    public function __construct()
    {
        $this->invasionService = app(InvasionService::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->raidCalculator = app(RaidCalculator::class);
        $this->raidHelper = app(RaidHelper::class);
    }

    /**
     * Perform a tactic action with Laravel model binding.
     */
    public function performAction(Dominion $dominion, RaidObjectiveTactic $tactic, array $data): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        if ($dominion->round->hasOffensiveActionsDisabled()) {
            throw new GameException('Raids have been disabled for the remainder of the round');
        }

        // Check if objective is active
        if ($tactic->objective->start_date > now() || $tactic->objective->end_date < now()) {
            //throw new GameException('This raid objective is not currently active');
        }

        switch ($tactic->type) {
            case 'hero':
                $result = $this->processHeroBattle($dominion, $tactic, $data);
                break;
            case 'invasion':
                $result = $this->processInvasion($dominion, $tactic, $data);
                break;
            default:
                $result = $this->processAction($dominion, $tactic, $data);
                break;
        }

        return $result;
    }

    /**
     * Process basic actions (espionage, exploration, investment, magic).
     */
    protected function processAction(Dominion $dominion, RaidObjectiveTactic $tactic, array $data): array
    {
        // Calculate costs and deductions
        $costs = $this->calculateCosts($dominion, $tactic, $data);
        $pointsEarned = $this->raidCalculator->getTacticPointsEarned($dominion, $tactic);

        DB::transaction(function () use ($dominion, $tactic, $costs, $pointsEarned) {
            // Apply costs
            foreach ($costs as $attr => $cost) {
                $dominion->{$attr} -= $cost;
            }

            // Save dominion changes
            $dominion->save(['event' => HistoryService::EVENT_ACTION_RAID_ACTION]);

            // Create contribution record
            RaidContribution::create([
                'realm_id' => $dominion->realm_id,
                'dominion_id' => $dominion->id,
                'raid_objective_id' => $tactic->raid_objective_id,
                'type' => $tactic->type,
                'score' => $pointsEarned,
            ]);
        });

        return [
            'message' => sprintf(
                'You have successfully completed %s for %s points.',
                $tactic->name,
                number_format($pointsEarned)
            ),
            'data' => [
                'tacticName' => $tactic->name,
                'pointsEarned' => $pointsEarned,
                'costs' => $costs,
            ]
        ];
    }

    protected function processHeroBattle(Dominion $dominion, RaidObjectiveTactic $tactic, array $data): array
    {
        if ($dominion->hero == null) {
            throw new GameException('You must have a hero to perform this action');
        }

        $tacticBattles = $dominion->hero->battles->where('raid_tactic_id', $tactic->id);

        foreach ($tacticBattles->where('finished', true) as $finishedBattle) {
            if ($finishedBattle->winner && $finishedBattle->winner->dominion_id == $dominion->id) {
                throw new GameException('You have already completed this objective');
            }
        }

        if ($tacticBattles->where('finished', false)->count() > 0) {
            throw new GameException('You already have a battle in progress');
        }

        $heroBattleService = app(HeroBattleService::class);
        $heroBattle = HeroBattle::create([
            'round_id' => $dominion->round_id,
            'raid_tactic_id' => $tactic->id,
            'pvp' => false,
        ]);
        $dominionCombatant = $heroBattleService->createCombatant($heroBattle, $dominion->hero);
        $enemyCount = $tactic->attributes['enemy_count'] ?? 1;
        foreach (range(1, $enemyCount) as $idx) {
            $enemyAttributes = $tactic->attributes;
            if ($idx > 1) {
                $enemyAttributes['name'] .= " {$idx}";
            }
            $heroBattleService->createNonPlayerCombatant($heroBattle, $enemyAttributes);
        }

        return [
            'message' => 'The battle begins!',
            'redirect' => route('dominion.heroes.battles'),
        ];
    }

    protected function processInvasion(Dominion $dominion, RaidObjectiveTactic $tactic, array $data): array
    {
        $units = array_map('intval', array_filter($data['unit']));

        $this->validateInvasionRequirements($dominion, $units);

        DB::transaction(function () use ($dominion, $tactic, $units) {
            $damageDealt = round($this->militaryCalculator->getOffensivePowerRaw($dominion, null, null, $units));
            $this->attackResult['attacker']['op'] = $damageDealt;

            $multiplier = 1;

            // Techs
            $multiplier += $dominion->getTechPerkMultiplier('wonder_damage');

            // Heroes
            if ($dominion->hero !== null) {
                $multiplier += $dominion->hero->getPerkMultiplier('wonder_attack_damage');
            }

            $damageDealt *= $multiplier;
            $this->attackResult['attacker']['damage'] = $damageDealt;

            $this->handleBoats($dominion, $units);
            $survivingUnits = $this->handleCasualties($dominion, $units, $tactic->attributes['casualties']);
            $this->handleReturningUnits($dominion, $survivingUnits);

            $this->attackResult['attacker']['unitsSent'] = $units;

            $dominion->morale -= 5;

            $this->attackEvent = GameEvent::create([
                'round_id' => $dominion->round->id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => RaidObjective::class,
                'target_id' => $tactic->raid_objective_id,
                'type' => 'raid_attacked',
                'data' => $this->attackResult
            ]);

            // Save dominion changes
            $dominion->save(['event' => HistoryService::EVENT_ACTION_RAID_ATTACKED]);

            // Create contribution record
            RaidContribution::create([
                'realm_id' => $dominion->realm_id,
                'dominion_id' => $dominion->id,
                'raid_objective_id' => $tactic->raid_objective_id,
                'type' => $tactic->type,
                'score' => $damageDealt,
            ]);
        });

        return [
            'message' => sprintf(
                'You have successfully completed %s for %s points.',
                $tactic->name,
                $this->attackResult['attacker']['damage']
            ),
            'data' => [
                'tacticName' => $tactic->name,
                'pointsEarned' => $this->attackResult['attacker']['damage']
            ],
            'redirect' => route('dominion.event', [$this->attackEvent->id])
        ];
    }

    protected function validateInvasionRequirements(Dominion $dominion, array $units): void
    {
        if (!$this->invasionService->hasAnyOP($dominion, $units)) {
            throw new GameException('You need to send at least some units');
        }

        if (!$this->invasionService->allUnitsHaveOP($dominion, $units)) {
            throw new GameException('You cannot send units that have no OP');
        }

        if (!$this->invasionService->hasEnoughUnitsAtHome($dominion, $units)) {
            throw new GameException('You don\'t have enough units at home to send this many units');
        }

        if (!$this->invasionService->hasEnoughBoats($dominion, $units)) {
            throw new GameException('You do not have enough boats to send this many units');
        }

        if (!$this->invasionService->hasEnoughMorale($dominion)) {
            throw new GameException('You do not have enough morale to attack');
        }

        if (!$this->invasionService->passes40PercentRule($dominion, null, $units)) {
            throw new GameException('You need to leave more DP units at home (40% rule)');
        }

        if (!$this->invasionService->passes54RatioRule($dominion, null, null, $units, true)) {
            throw new GameException('You are sending out too much OP, based on your new home DP (5:4 rule)');
        }
    }

    /**
     * Calculate tactic costs.
     */
    protected function calculateCosts(Dominion $dominion, RaidObjectiveTactic $tactic, array $data): array
    {
        $costs = [];

        switch ($tactic->type) {
            case 'espionage':
                $strengthCost = $tactic->attributes['strength_cost'];
                $moraleCost = $tactic->attributes['morale_cost'];
                if ($dominion->spy_strength < $strengthCost) {
                    throw new GameException('You do not have enough spy strength');
                }
                if ($dominion->morale < $moraleCost) {
                    throw new GameException('You do not have enough morale');
                }
                $costs['spy_strength'] = $strengthCost;
                $costs['morale'] = $moraleCost;
                break;

            case 'exploration':
                $drafteeCost = $tactic->attributes['draftee_cost'];
                $moraleCost = $tactic->attributes['morale_cost'];
                if ($dominion->military_draftees < $drafteeCost) {
                    throw new GameException('You do not have enough draftees');
                }
                if ($dominion->morale < $moraleCost) {
                    throw new GameException('You do not have enough morale');
                }
                $costs['military_draftees'] = $drafteeCost;
                $costs['morale'] = $moraleCost;
                break;

            case 'investment':
                $resourceType = $tactic->attributes['resource'];
                $resourceCost = $tactic->attributes['amount'];
                if ($dominion->{"resource_{$resourceType}"} < $resourceCost) {
                    throw new GameException("You do not have enough {$resourceType}");
                }
                $costs["resource_{$resourceType}"] = $resourceCost;
                break;

            case 'magic':
                $wizardStrength = $tactic->attributes['strength_cost'];
                $actualManaCost = $this->raidCalculator->getTacticManaCost($dominion, $tactic);
                if ($dominion->wizard_strength < $wizardStrength) {
                    throw new GameException('You do not have enough wizard strength');
                }
                if ($dominion->resource_mana < $actualManaCost) {
                    throw new GameException('You do not have enough mana');
                }
                $costs['wizard_strength'] = $wizardStrength;
                $costs['resource_mana'] = $actualManaCost;
                break;
        }

        return $costs;
    }

    /**
     * Handles the returning boats.
     *
     * @param Dominion $dominion
     * @param array $units
     */
    protected function handleBoats(Dominion $dominion, array $units): void
    {
        $unitsThatNeedsBoatsByReturnHours = [];
        // Calculate boats sent
        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            if ($unit->need_boat) {
                $hours = $this->invasionService->getUnitReturnHoursForSlot($dominion, $unit->slot);

                if (!isset($unitsThatNeedsBoatsByReturnHours[$hours])) {
                    $unitsThatNeedsBoatsByReturnHours[$hours] = 0;
                }

                $unitsThatNeedsBoatsByReturnHours[$hours] += (int)$units[$unit->slot];
            }
        }

        // Queue returning boats
        foreach ($unitsThatNeedsBoatsByReturnHours as $hours => $amountUnits) {
            $boatsByReturnHourGroup = (int)rfloor($amountUnits / $this->militaryCalculator->getBoatCapacity($dominion));

            $dominion->resource_boats -= $boatsByReturnHourGroup;

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                ['resource_boats' => $boatsByReturnHourGroup],
                $hours
            );
        }
    }

    /**
     * Handles offensive casualties for the attacking dominion.
     *
     * @param Dominion $dominion
     * @param array $units
     * @param float $casualtyPercentage
     * @return array All the units that survived and will return home
     */
    protected function handleCasualties(Dominion $dominion, array $units, float $casualtyPercentage): array
    {
        $offensiveCasualtiesPercentage = $casualtyPercentage / 100;
        $offensiveUnitsLost = [];

        foreach ($units as $slot => $amount) {
            $unitsToKill = (int)rceil($amount * $offensiveCasualtiesPercentage);
            $offensiveUnitsLost[$slot] = $unitsToKill;

            $fixedCasualtiesPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties');
            if ($fixedCasualtiesPerk) {
                $fixedCasualtiesRatio = $fixedCasualtiesPerk / 100;
                $unitsActuallyKilled = (int)rceil($amount * $fixedCasualtiesRatio);
                $offensiveUnitsLost[$slot] = $unitsActuallyKilled;
            }
        }

        foreach ($offensiveUnitsLost as $slot => &$amount) {
            if ($amount > 0) {
                // Actually kill the units. RIP in peace, glorious warriors ;_;7
                $dominion->{"military_unit{$slot}"} -= $amount;

                $this->attackResult['attacker']['unitsLost'][$slot] = $amount;
            }
        }
        unset($amount);

        $survivingUnits = $units;

        foreach ($units as $slot => $amount) {
            if (isset($offensiveUnitsLost[$slot])) {
                $survivingUnits[$slot] -= $offensiveUnitsLost[$slot];
            }
        }

        return $survivingUnits;
    }

    /**
     * Handles the surviving units returning home.
     *
     * @param Dominion $dominion
     * @param array $units
     */
    protected function handleReturningUnits(Dominion $dominion, array $units): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $unitKey = "military_unit{$i}";
            $returningAmount = 0;

            if (array_key_exists($i, $units)) {
                $returningAmount += $units[$i];
                $dominion->$unitKey -= $units[$i];
            }

            if ($returningAmount === 0) {
                continue;
            }

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                [$unitKey => $returningAmount],
                $this->invasionService->getUnitReturnHoursForSlot($dominion, $i)
            );
        }
    }
}
