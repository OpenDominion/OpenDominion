<?php

namespace OpenDominion\HeroCombat\Persistence;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\HeroCombat\Content\Loadouts\HeroClassLoadouts;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\Models\Hero;

/**
 * Adds players' heroes to a battle being set up.
 */
class CombatantFactory
{
    public const DEFAULT_TIME_BANK = 2 * 60 * 60;
    public const DEFAULT_STRATEGY = 'balanced';

    public function __construct(
        protected HeroCalculator $heroCalculator,
        protected HeroClassLoadouts $loadouts,
    ) {
    }

    /**
     * @param string[] $grantedAbilities extra abilities (e.g. from the encounter)
     */
    public function addHero(Battle $battle, Hero $hero, int $team, array $grantedAbilities = []): CombatantState
    {
        $stats = $this->heroCalculator->getHeroCombatStats($hero);

        $combatant = new CombatantState(
            id: 0,
            name: $hero->name,
            team: $team,
            baseStats: $stats,
            currentHealth: $stats['health'],
            abilities: $this->loadouts->withGrants($this->loadouts->abilitiesFor($hero->class), $grantedAbilities),
            ai: self::DEFAULT_STRATEGY,
            heroId: $hero->id,
            dominionId: $hero->dominion_id,
        );
        $combatant->timeBank = self::DEFAULT_TIME_BANK;

        $combatant->id = $battle->spawner->spawn($battle->state, $combatant);
        $battle->state->addCombatant($combatant);

        foreach ($this->loadouts->passivesFor($hero->class) as $passive) {
            $battle->effects->apply($combatant, $passive);
        }

        return $combatant;
    }

    /**
     * Class keys a hero has (current class plus any classes in its history).
     *
     * @return string[]
     */
    public function heroClasses(Hero $hero): array
    {
        $classes = [$hero->class];
        foreach ($hero->class_data ?? [] as $classEntry) {
            $class = $classEntry['key'] ?? null;
            if ($class !== null && $this->heroCalculator->heroHasClass($hero, $class)) {
                $classes[] = $class;
            }
        }

        return array_values(array_unique(array_filter($classes)));
    }
}
