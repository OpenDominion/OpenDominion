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
            HeroHeroUpgrade::insert([
                'hero_id' => $dominion->hero->id,
                'hero_upgrade_id' => $upgrade->id
            ]);

            // Apply Status Effects
            if ($upgrade->type === 'effect') {
                $statusEffectSpell = Spell::where('key', $upgrade->key)->first();
                if ($statusEffectSpell !== null) {
                    DominionSpell::insert([
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
            if ($dominion->round->daysInRound() < 10) {
                throw new GameException('You cannot select an advanced hero class until the 10th day of the round.');
            }
            if ($dominion->{$selectedClass['requirement_stat']} < $selectedClass['requirement_value']) {
                throw new GameException('You do not meet the requirements to select this hero class.');
            }
        }

        DB::transaction(function () use ($dominion, $name, $selectedClass) {
            HeroHeroUpgrade::where('hero_id', $dominion->hero->id)->delete();

            // Starting XP
            $xp = (int) min($dominion->hero->experience, 10000) / 2;
            if ($selectedClass['class_type'] === 'advanced') {
                $xp = $dominion->{$selectedClass['starting_xp_stat']} * $selectedClass['starting_xp_coefficient'];

                // Advanced Class Upgrades
                $advancedUpgrades = HeroUpgrade::query()
                    ->where('level', 0)
                    ->where('type', 'directive')
                    ->get()
                    ->filter(function ($upgrade) use ($selectedClass) {
                        return in_array($selectedClass['key'], $upgrade->classes);
                    });
                foreach ($advancedUpgrades as $advancedUpgrade) {
                    HeroHeroUpgrade::insert([
                        'hero_id' => $dominion->hero->id,
                        'hero_upgrade_id' => $advancedUpgrade->id
                    ]);
                }
            }

            $dominion->hero()->update([
                'name' => $name,
                'class' => $selectedClass['key'],
                'experience' => $xp
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
}
