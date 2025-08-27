<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\DominionTech;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\HeroHeroUpgrade;
use OpenDominion\Models\HeroUpgrade;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

class HeroActionService
{
    use DominionGuardsTrait;

    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var HeroHelper */
    protected $heroHelper;

    /**
     * HeroActionService constructor.
     */
    public function __construct()
    {
        $this->heroCalculator = app(HeroCalculator::class);
        $this->heroHelper = app(HeroHelper::class);
    }

    /**
     * Does a hero upgrade unlock action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $key
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function unlock(Dominion $dominion, string $key): array
    {
        $this->guardLockedDominion($dominion);

        if ($dominion->hero === null) {
            throw new GameException('You have not selected a hero.');
        }

        // Get the upgrade information
        $upgrade = HeroUpgrade::query()
            ->where('key', $key)
            ->first();
        if ($upgrade == null) {
            throw new LogicException('Failed to find hero upgrade ' . $key);
        }

        // Check prerequisites
        if (!$this->heroCalculator->canUnlockUpgrade($dominion->hero, $upgrade)) {
            throw new GameException('You do not meet the requirements to unlock this hero upgrade.');
        }

        DB::transaction(function () use ($dominion, $upgrade) {
            HeroHeroUpgrade::create([
                'hero_id' => $dominion->hero->id,
                'hero_upgrade_id' => $upgrade->id
            ]);

            // Apply Status Effects
            if ($upgrade->type === 'effect') {
                $statusEffectSpell = Spell::where('key', $upgrade->key)->first();
                if ($statusEffectSpell !== null) {
                    DominionSpell::create([
                        'dominion_id' => $dominion->id,
                        'spell_id' => $statusEffectSpell->id,
                        'duration' => $statusEffectSpell->duration,
                        'cast_by_dominion_id' => $dominion->id,
                    ]);
                }
            }

            // Apply Immediate Effects
            if ($upgrade->type === 'immediate') {
                // Special case for tech refund
                $techRefundMultiplier = $upgrade->getPerkValue('tech_refund') / 100;
                if ($techRefundMultiplier) {
                    $techCalculator = app(TechCalculator::class);
                    $techCost = $techCalculator->getTechCost($dominion);
                    $techCount = count($dominion->techs);
                    $fullRefundCount = min($techCount, 5);
                    $partialRefundCount = $techCount - $fullRefundCount;
                    $techRefund = (int) ($techCost * ($fullRefundCount + ($partialRefundCount * $techRefundMultiplier)));
                    $dominion->resource_tech += $techRefund;
                    DominionTech::where('dominion_id', $dominion->id)->delete();
                }
            }

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_HERO,
                'action' => $upgrade->key
            ]);
        });

        return [
            'message' => sprintf(
                'You have unlocked %s.',
                $upgrade->name
            )
        ];
    }

    /**
     * Does a create hero action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $name
     * @param string $class
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function create(Dominion $dominion, string $name, string $class): array
    {
        $this->guardLockedDominion($dominion);

        if (!$dominion->heroes->isEmpty()) {
            throw new GameException('You can only have one hero at a time.');
        }

        $heroClasses = $this->heroHelper->getBasicClasses()->keyBy('key');
        if (!isset($heroClasses[$class])) {
            throw new LogicException('Failed to find hero class ' . $class);
        }
        $selectedClass = $heroClasses[$class];

        DB::transaction(function () use ($dominion, $name, $selectedClass) {
            $dominion->heroes()->create([
                'name' => $name,
                'class' => $selectedClass['key']
            ]);

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_HERO_CREATE,
                'action' => $selectedClass['key']
            ]);
        });

        return [
            'message' => sprintf(
                'You have selected the %s hero.',
                $selectedClass['name']
            )
        ];
    }

    /**
     * Does a retire hero action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $name
     * @param string $class
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function retire(Dominion $dominion, string $name, string $class): array
    {
        $this->guardLockedDominion($dominion);

        if ($dominion->heroes->isEmpty()) {
            throw new GameException('You do not have a hero to retire.');
        }

        // TODO: Add cooldown

        $heroClasses = $this->heroHelper->getClasses()->keyBy('key');
        $currentHeroClass = $heroClasses[$dominion->hero->class] ?? null;
        if ($currentHeroClass['class_type'] === 'advanced') {
            throw new GameException('You cannot retire an advanced hero class.');
        }
        $selectedClass = $heroClasses[$class] ?? null;
        if ($selectedClass === null) {
            throw new LogicException('Failed to find hero class ' . $class);
        }

        if ($selectedClass['class_type'] === 'advanced') {
            if ($dominion->round->daysInRound() < 5) {
                throw new GameException('You cannot select an advanced hero class until the 5th day of the round.');
            }
            if ($dominion->{$selectedClass['requirement_stat']} < $selectedClass['requirement_value']) {
                throw new GameException('You do not meet the requirements to select this hero class.');
            }
        }

        DB::transaction(function () use ($dominion, $name, $selectedClass) {
            // TODO: Consider deleting upgrades for Scion?
            // HeroHeroUpgrade::where('hero_id', $dominion->hero->id)->delete();

            if ($selectedClass['class_type'] === 'advanced') {
                // Advanced Class Upgrades
                $advancedUpgrades = HeroUpgrade::query()
                    ->where('level', 0)
                    ->where('type', 'directive')
                    ->get()
                    ->filter(function ($upgrade) use ($selectedClass) {
                        return in_array($selectedClass['key'], $upgrade->classes);
                    });
                foreach ($advancedUpgrades as $advancedUpgrade) {
                    HeroHeroUpgrade::create([
                        'hero_id' => $dominion->hero->id,
                        'hero_upgrade_id' => $advancedUpgrade->id
                    ]);
                }
            }

            $xp = 0;
            $classData = $dominion->hero->class_data;
            $existingClassData = collect($classData)->where('key', $selectedClass['key'])->first();
            if ($existingClassData !== null) {
                $xp = $existingClassData['experience'];
            }
            $currentClassData = collect($classData)->where('key', $dominion->hero->class)->first();
            if ($currentClassData !== null) {
                $classData[$dominion->hero->class]['experience'] = $dominion->hero->experience;
            } else {
                $classData[$dominion->hero->class] = [
                    'key' => $dominion->hero->class,
                    'experience' => $dominion->hero->experience,
                    'perk_type' => $this->heroHelper->getPassivePerkType($dominion->hero->class),
                ];
            }

            $dominion->hero()->update([
                'name' => $name,
                'class' => $selectedClass['key'],
                'experience' => $xp,
                'class_data' => $classData,
            ]);

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_HERO_RETIRE,
                'action' => $selectedClass['key']
            ]);
        });

        return [
            'message' => sprintf(
                'Your hero has been retired. You have selected the %s hero.',
                $selectedClass['name']
            )
        ];
    }

    public function updateCombatant(Dominion $dominion, HeroCombatant $combatant, string $strategy, bool $automated)
    {
        $this->guardLockedDominion($dominion);

        if ($dominion->hero === null || $dominion->hero->id !== $combatant->hero_id) {
            throw new GameException('You cannot change settings for another hero.');
        }

        if ($combatant->battle->finished) {
            throw new GameException('You cannot change settings for a battle that has ended.');
        }

        if ($combatant->time_bank <= 0 && $automated == false) {
            throw new GameException('You ran out of time and can no longer set manual actions.');
        }

        $validStrategies = $this->heroHelper->getCombatStrategies();
        if (!in_array($strategy, $validStrategies)) {
            throw new GameException('Invalid strategy.');
        }

        $combatant->strategy = $strategy;
        $combatant->automated = $automated;
        $combatant->save();
    }

    public function queueAction(Dominion $dominion, HeroCombatant $combatant, HeroCombatant $target, string $action)
    {
        $this->guardLockedDominion($dominion);

        if ($dominion->hero === null || $dominion->hero->id !== $combatant->hero_id) {
            throw new GameException('You cannot queue actions for another hero.');
        }

        if ($combatant->battle->finished) {
            throw new GameException('You cannot queue actions for a battle that has ended.');
        }

        if ($combatant->hero_battle_id !== $target->hero_battle_id || $target->current_health <= 0) {
            throw new GameException('Invalid target.');
        }

        if ($combatant->time_bank <= 0) {
            throw new GameException('You ran out of time and can no longer set manual actions.');
        }

        $validActions = $this->heroHelper->getCombatActions($combatant);
        if (!in_array($action, $validActions)) {
            throw new GameException('Invalid action.');
        }

        $actions = $combatant->actions ?? [];
        if (count($actions) >= 6) {
            throw new GameException('You cannot queue more than 6 actions.');
        }

        array_push($actions, ['action' => $action, 'target' => $target->id]);
        $combatant->actions = $actions;
        $combatant->automated = false;
        $combatant->save();
    }

    public function dequeueAction(Dominion $dominion, HeroCombatant $combatant, int $index)
    {
        $this->guardLockedDominion($dominion);

        if ($dominion->hero === null || $dominion->hero->id !== $combatant->hero_id) {
            throw new GameException('You cannot delete actions for another hero.');
        }

        if ($combatant->battle->finished) {
            throw new GameException('You cannot delete actions for a battle that has ended.');
        }

        $actions = $combatant->actions ?? [];
        if (!isset($actions[$index])) {
            throw new GameException('Invalid action.');
        }

        array_splice($actions, $index, 1);
        $combatant->actions = $actions;
        $combatant->save();
    }
}
