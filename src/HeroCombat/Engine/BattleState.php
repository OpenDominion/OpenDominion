<?php

namespace OpenDominion\HeroCombat\Engine;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Everything the engine needs to know about a battle, independent of persistence.
 */
final class BattleState
{
    public const MODE_PVP = 'pvp';
    public const MODE_PRACTICE = 'practice';
    public const MODE_RAID = 'raid';

    /** @var array<int, CombatantState> keyed by id */
    public array $combatants = [];

    /** @var array<int, EffectInstance[]> keyed by team */
    public array $teamEffects = [];

    /** @var EffectInstance[] */
    public array $fieldEffects = [];

    /** @var array<int, Intent> intents for the turn being resolved, keyed by actor id */
    public array $intents = [];

    public bool $finished = false;

    public ?int $winningTeam = null;

    public int $effectSequence = 0;

    public function __construct(
        public ?int $id,
        public int $turn,
        public int $seed,
        public string $mode = self::MODE_PRACTICE,
        public ?string $encounterKey = null,
    ) {
    }

    public function addCombatant(CombatantState $combatant): void
    {
        $this->combatants[$combatant->id] = $combatant;
    }

    public function nextEffectSequence(): int
    {
        return ++$this->effectSequence;
    }

    /**
     * @return int[]
     */
    public function teams(): array
    {
        return array_values(array_unique(array_map(fn (CombatantState $c) => $c->team, $this->combatants)));
    }
}
