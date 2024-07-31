<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBonus;
use OpenDominion\Models\HeroHeroBonus;
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
     * Does a hero bonus unlock action for a Dominion.
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

        // Get the bonus information
        $bonusToUnlock = HeroBonus::query()
            ->where('key', $key)
            ->first();
        if ($bonusToUnlock == null) {
            throw new LogicException('Failed to find hero bonus ' . $key);
        }

        // Check prerequisites
        if (!$this->heroCalculator->canUnlockBonus($dominion->hero, $bonusToUnlock)) {
            throw new GameException('You do not meet the requirements to unlock this hero bonus.');
        }

        DB::transaction(function () use ($dominion, $bonusToUnlock) {
            HeroHeroBonus::create([
                'hero_id' => $dominion->hero->id,
                'hero_bonus_id' => $bonusToUnlock->id
            ]);

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_HERO,
                'action' => $bonusToUnlock->key
            ]);
        });

        return [
            'message' => sprintf(
                'You have unlocked %s.',
                $bonusToUnlock->name
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
            // TODO: Dynamic Requirements
            if ($dominion->stat_attacking_success < 10) {
                throw new GameException('You do not meet the requirements to select this hero class.');
            }
        }

        DB::transaction(function () use ($dominion, $name, $selectedClass) {
            $dominion->hero->bonuses()->delete();

            $xp = (int) min($dominion->hero->experience, 10000) / 2;
            if ($selectedClass['class_type'] === 'advanced') {
                $xp = 0;
                // Advanced Class Bonuses
                $advancedBonuses = HeroBonus::where('level', 0)->get()->filter(function ($bonus) use ($selectedClass) {
                    return in_array($selectedClass['key'], $bonus->classes);
                });
                foreach ($advancedBonuses as $advancedBonus) {
                    HeroHeroBonus::create([
                        'hero_id' => $dominion->hero->id,
                        'hero_bonus_id' => $advancedBonus->id
                    ]);
                }
                // TODO: Activate any status effects
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
