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

    /** Template key of the ally each side gets in a 2v2 PvP battle. */
    public const ALLY_TEMPLATE = 'sellsword';

    public function __construct(
        protected HeroCalculator $heroCalculator,
        protected HeroClassLoadouts $loadouts,
    ) {
    }

    /**
     * @param string[] $grantedAbilities extra abilities (e.g. from the encounter)
     * @param int|null $controllerDominionId who pilots the hero; defaults to its owner
     */
    public function addHero(Battle $battle, Hero $hero, int $team, array $grantedAbilities = [], ?int $controllerDominionId = null): CombatantState
    {
        $stats = $this->heroCalculator->getHeroCombatStats($hero);
        $loadoutKeys = $this->loadoutKeys($hero);
        $loadoutAbilities = array_filter($loadoutKeys, fn (string $key) => $battle->registry->hasAbility($key));

        $combatant = new CombatantState(
            id: 0,
            name: $hero->name,
            team: $team,
            baseStats: $stats,
            currentHealth: $stats['health'],
            abilities: $this->loadouts->withGrants(HeroClassLoadouts::BASIC_ABILITIES, array_merge($loadoutAbilities, $grantedAbilities)),
            ai: self::DEFAULT_STRATEGY,
            heroId: $hero->id,
            dominionId: $hero->dominion_id,
        );
        $combatant->timeBank = self::DEFAULT_TIME_BANK;
        $combatant->controllerDominionId = $controllerDominionId ?? $hero->dominion_id;

        $combatant->id = $battle->spawner->spawn($battle->state, $combatant);
        $battle->state->addCombatant($combatant);

        foreach ($loadoutKeys as $key) {
            if ($battle->registry->hasEffect($key)) {
                $battle->effects->apply($combatant, $key);
            }
        }

        return $combatant;
    }

    /**
     * A templated ally for a 2v2 PvP battle: base stats at the given level, basic abilities
     * only and no loadout, piloted by the dominion alongside its own hero. Both sides get an
     * identical one, and it earns nothing: it has no hero.
     */
    public function addAlly(Battle $battle, int $team, int $level, int $controllerDominionId, string $name): CombatantState
    {
        $stats = $this->heroCalculator->getBaseCombatStats($level);

        $combatant = new CombatantState(
            id: 0,
            name: $name,
            team: $team,
            baseStats: $stats,
            currentHealth: $stats['health'],
            abilities: HeroClassLoadouts::BASIC_ABILITIES,
            ai: self::DEFAULT_STRATEGY,
            templateKey: self::ALLY_TEMPLATE,
        );
        $combatant->timeBank = self::DEFAULT_TIME_BANK;
        $combatant->controllerDominionId = $controllerDominionId;

        $combatant->id = $battle->spawner->spawn($battle->state, $combatant);
        $battle->state->addCombatant($combatant);

        return $combatant;
    }

    /**
     * The hero's equipped option keys. A key can name an ability, an innate effect, or neither
     * for gear, which only changes stats.
     *
     * @return string[]
     */
    public function loadoutKeys(Hero $hero): array
    {
        return collect($this->heroCalculator->getLoadout($hero))
            ->flatten()
            ->pluck('key')
            ->all();
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
