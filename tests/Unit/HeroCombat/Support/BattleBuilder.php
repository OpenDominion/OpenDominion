<?php

namespace OpenDominion\Tests\Unit\HeroCombat\Support;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\BattleState;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\EncounterContext;
use OpenDominion\HeroCombat\Engine\InMemorySpawner;
use OpenDominion\HeroCombat\Engine\Random\RandomSource;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Providers\HeroCombatServiceProvider;

/**
 * Builds in-memory battles for engine tests (no database).
 */
class BattleBuilder
{
    public const BASE_STATS = [
        'health' => 100,
        'attack' => 40,
        'defense' => 20,
        'evasion' => 0,
        'focus' => 10,
        'counter' => 10,
        'recover' => 20,
    ];

    private BattleState $state;

    private ?CombatRegistry $registry = null;

    private int $nextId = 1;

    private bool $spawnRoster = false;

    public function __construct(?string $encounterKey = null)
    {
        $this->state = new BattleState(id: 1, turn: 1, seed: 1, encounterKey: $encounterKey);
    }

    public static function make(?string $encounterKey = null): self
    {
        return new self($encounterKey);
    }

    /**
     * Spawn the encounter's roster on team 2 when building.
     */
    public function withRoster(): self
    {
        $this->spawnRoster = true;

        return $this;
    }

    public function withRegistry(CombatRegistry $registry): self
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @param array<string, int> $stats
     * @param string[] $abilities
     */
    public function hero(string $name, int $team = 1, array $stats = [], array $abilities = ['attack', 'defend', 'focus', 'counter', 'recover']): self
    {
        $combatant = $this->combatant($name, $team, $stats, $abilities);
        $combatant->heroId = $combatant->id;
        $combatant->dominionId = $combatant->id;

        return $this;
    }

    /**
     * @param array<string, int> $stats
     * @param string[] $abilities
     */
    public function npc(string $name, int $team = 2, array $stats = [], array $abilities = ['attack', 'defend', 'focus', 'counter', 'recover'], string $ai = 'attack'): self
    {
        $combatant = $this->combatant($name, $team, $stats, $abilities);
        $combatant->ai = $ai;
        $combatant->automated = true;

        return $this;
    }

    public function build(?RandomSource $random = null): Battle
    {
        $battle = new Battle(
            $this->state,
            $this->registry ?? HeroCombatServiceProvider::buildRegistry(),
            $random ?? new ScriptedRandomSource(),
            new InMemorySpawner(),
        );

        if ($this->spawnRoster && $battle->encounter !== null) {
            foreach ($battle->encounter->roster(new EncounterContext()) as $entry) {
                $battle->spawn($battle->registry->enemy($entry['template']), 2, $entry['name'] ?? null, $entry['stats'] ?? [], $entry['effects'] ?? []);
            }
        }

        return $battle;
    }

    /**
     * @param array<string, int> $stats
     * @param string[] $abilities
     */
    private function combatant(string $name, int $team, array $stats, array $abilities): CombatantState
    {
        $stats = array_merge(self::BASE_STATS, $stats);
        $combatant = new CombatantState(
            id: $this->nextId++,
            name: $name,
            team: $team,
            baseStats: $stats,
            currentHealth: $stats['health'],
            abilities: $abilities,
        );
        $this->state->addCombatant($combatant);

        return $combatant;
    }
}
