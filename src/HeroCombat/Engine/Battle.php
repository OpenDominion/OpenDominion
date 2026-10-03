<?php

namespace OpenDominion\HeroCombat\Engine;

use OpenDominion\HeroCombat\Contracts\CombatantSpawner;
use OpenDominion\HeroCombat\Contracts\Encounter;
use OpenDominion\HeroCombat\Contracts\EnemyTemplate;
use OpenDominion\HeroCombat\Engine\Damage\DamageResolver;
use OpenDominion\HeroCombat\Engine\Effects\EffectDispatcher;
use OpenDominion\HeroCombat\Engine\Effects\EffectManager;
use OpenDominion\HeroCombat\Engine\Events\BattleEvent;
use OpenDominion\HeroCombat\Engine\Events\BattleLog;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Random\RandomSource;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\HeroCombat\Engine\Stats\StatCalculator;
use OpenDominion\HeroCombat\Engine\Targeting\TargetResolver;
use OpenDominion\HeroCombat\Engine\Victory\LastTeamStanding;
use OpenDominion\HeroCombat\Engine\Victory\VictoryCondition;
use OpenDominion\HeroCombat\Registry\CombatRegistry;

/**
 * Runtime facade over a BattleState: the object abilities, effects and AI interact with.
 */
final class Battle
{
    public readonly StatCalculator $stats;

    public readonly EffectManager $effects;

    public readonly EffectDispatcher $dispatcher;

    public readonly DamageResolver $damage;

    public readonly TargetResolver $targets;

    public readonly ?Encounter $encounter;

    public function __construct(
        public readonly BattleState $state,
        public readonly CombatRegistry $registry,
        public RandomSource $random,
        public readonly CombatantSpawner $spawner,
        public readonly BattleLog $log = new BattleLog(),
    ) {
        $this->stats = new StatCalculator($this);
        $this->effects = new EffectManager($this);
        $this->dispatcher = new EffectDispatcher($this);
        $this->damage = new DamageResolver($this);
        $this->targets = new TargetResolver($this);
        $this->encounter = $state->encounterKey !== null && $registry->hasEncounter($state->encounterKey)
            ? $registry->encounter($state->encounterKey)
            : null;
    }

    public function turn(): int
    {
        return $this->state->turn;
    }

    public function combatant(?int $id): ?CombatantState
    {
        return $id === null ? null : ($this->state->combatants[$id] ?? null);
    }

    /**
     * @return CombatantState[] ordered by id
     */
    public function combatants(): array
    {
        $combatants = $this->state->combatants;
        ksort($combatants);

        return array_values($combatants);
    }

    /**
     * @return CombatantState[] ordered by id
     */
    public function living(): array
    {
        return array_values(array_filter($this->combatants(), fn (CombatantState $c) => $c->isAlive()));
    }

    /**
     * @return CombatantState[]
     */
    public function enemiesOf(CombatantState $combatant, bool $livingOnly = true): array
    {
        $pool = $livingOnly ? $this->living() : $this->combatants();

        return array_values(array_filter($pool, fn (CombatantState $c) => $c->team !== $combatant->team));
    }

    /**
     * @return CombatantState[]
     */
    public function alliesOf(CombatantState $combatant, bool $includeSelf = true, bool $livingOnly = true): array
    {
        $pool = $livingOnly ? $this->living() : $this->combatants();

        return array_values(array_filter(
            $pool,
            fn (CombatantState $c) => $c->team === $combatant->team && ($includeSelf || $c->id !== $combatant->id),
        ));
    }

    /**
     * @return CombatantState[]
     */
    public function withTemplate(string $templateKey, bool $livingOnly = true): array
    {
        $pool = $livingOnly ? $this->living() : $this->combatants();

        return array_values(array_filter($pool, fn (CombatantState $c) => $c->templateKey === $templateKey));
    }

    public function stat(CombatantState $combatant, Stat $stat): int
    {
        return $this->stats->get($combatant, $stat);
    }

    public function maxHealth(CombatantState $combatant): int
    {
        return $this->stats->maxHealth($combatant);
    }

    public function hasTag(CombatantState $combatant, CombatTag $tag): bool
    {
        return $this->effects->hasTag($combatant, $tag);
    }

    public function intentOf(CombatantState $combatant): ?Intent
    {
        return $this->state->intents[$combatant->id] ?? null;
    }

    /**
     * Heals a living combatant up to its maximum health. Returns the amount healed.
     */
    public function heal(CombatantState $target, int $amount, ?CombatantState $source = null): int
    {
        if (!$target->isAlive() || $amount <= 0) {
            return 0;
        }

        $healed = min($amount, max(0, $this->maxHealth($target) - $target->currentHealth));
        $target->currentHealth += $healed;

        $entry = $this->log->current();
        if ($entry !== null && $entry->actorId === $target->id) {
            $entry->health += $healed;
        }

        $this->event(new BattleEvent(EventType::Heal, [
            'source' => $source?->id,
            'target' => $target->id,
            'amount' => $healed,
        ]));

        return $healed;
    }

    /**
     * Sets health directly, bypassing the damage pipeline (scripted effects only).
     */
    public function setHealth(CombatantState $target, int $health): void
    {
        $target->currentHealth = max(0, min($health, $this->maxHealth($target)));
    }

    public function say(?CombatantState $actor, string $text): void
    {
        $this->log->line($this->state->turn, $actor?->id, $text);
    }

    public function event(BattleEvent $event): void
    {
        $this->log->event($this->state->turn, $event);
    }

    /**
     * Creates a combatant from an enemy template, gives it an id and adds it to the battle.
     *
     * @param array<string, int> $statOverrides
     * @param array<string, array<string, mixed>> $effectOverrides
     */
    public function spawn(
        EnemyTemplate $template,
        int $team,
        ?string $name = null,
        array $statOverrides = [],
        array $effectOverrides = [],
    ): CombatantState {
        $stats = array_merge($template->stats(), $statOverrides);
        $combatant = new CombatantState(
            id: 0,
            name: $name ?? $template->name(),
            team: $team,
            baseStats: $stats,
            currentHealth: $stats[Stat::Health->value],
            abilities: $template->abilities(),
            ai: $template->ai(),
            templateKey: $template->key(),
        );
        $combatant->automated = true;

        $combatant->id = $this->spawner->spawn($this->state, $combatant);
        $this->state->addCombatant($combatant);

        foreach (array_merge($template->effects(), $effectOverrides) as $effectKey => $data) {
            $this->effects->apply($combatant, $effectKey, null, 1, $data);
        }

        return $combatant;
    }

    /**
     * Summons a template onto a team mid-battle.
     */
    public function summon(string $templateKey, int $team, ?string $name = null, ?CombatantState $summoner = null): CombatantState
    {
        $combatant = $this->spawn($this->registry->enemy($templateKey), $team, $name);

        $this->event(new BattleEvent(EventType::Summoned, [
            'source' => $summoner?->id,
            'target' => $combatant->id,
            'template' => $templateKey,
        ]));

        return $combatant;
    }

    public function victoryCondition(): VictoryCondition
    {
        return $this->encounter?->victoryCondition() ?? new LastTeamStanding();
    }
}
