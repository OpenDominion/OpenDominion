<?php

namespace OpenDominion\HeroCombat\Engine;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

/**
 * Runtime representation of one combatant. Holds only state; all rules live in
 * the engine services and content classes.
 */
final class CombatantState
{
    /** @var EffectInstance[] */
    public array $effects = [];

    /** @var array<string, int> ability key => turn on which it is usable again */
    public array $cooldowns = [];

    /** @var array<string, int> ability key => charges remaining */
    public array $charges = [];

    /** @var array<int, array{ability: string, target: int|null}> */
    public array $queue = [];

    public ?string $lastAction = null;

    public bool $automated = false;

    public int $timeBank = 0;

    /** True once death triggers have run for the current downed state. */
    public bool $deathProcessed = false;

    /**
     * @param array<string, int> $baseStats keyed by Stat value; 'health' is maximum health
     * @param string[] $abilities active ability keys
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $team,
        public array $baseStats,
        public int $currentHealth,
        public array $abilities = [],
        public string $ai = 'balanced',
        public ?int $heroId = null,
        public ?int $dominionId = null,
        public ?string $templateKey = null,
    ) {
    }

    public function isHuman(): bool
    {
        return $this->heroId !== null;
    }

    public function isAlive(): bool
    {
        return $this->currentHealth > 0;
    }

    public function baseStat(Stat $stat): int
    {
        return (int) ($this->baseStats[$stat->value] ?? 0);
    }

    public function hasAbility(string $key): bool
    {
        return in_array($key, $this->abilities, true);
    }

    public function grantAbility(string $key): void
    {
        if (!$this->hasAbility($key)) {
            $this->abilities[] = $key;
        }
    }

    public function revokeAbility(string $key): void
    {
        $this->abilities = array_values(array_filter($this->abilities, fn ($ability) => $ability !== $key));
    }

    public function isReady(): bool
    {
        return !$this->isHuman() || $this->automated || count($this->queue) > 0;
    }
}
