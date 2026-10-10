<?php

namespace OpenDominion\HeroCombat\Registry;

use OpenDominion\Exceptions\GameException;
use OpenDominion\HeroCombat\Contracts\Ability;
use OpenDominion\HeroCombat\Contracts\AiStrategy;
use OpenDominion\HeroCombat\Contracts\Effect;
use OpenDominion\HeroCombat\Contracts\Encounter;
use OpenDominion\HeroCombat\Contracts\EnemyTemplate;

/**
 * Explicit key => definition lookup for all combat content. Content is registered in
 * HeroCombatServiceProvider; there is no auto-discovery.
 */
class CombatRegistry
{
    /** @var array<string, Ability> */
    protected array $abilities = [];

    /** @var array<string, Effect> */
    protected array $effects = [];

    /** @var array<string, AiStrategy> */
    protected array $strategies = [];

    /** @var array<string, EnemyTemplate> */
    protected array $enemies = [];

    /** @var array<string, Encounter> */
    protected array $encounters = [];

    public function registerAbility(Ability $ability): static
    {
        $this->abilities[$ability->key()] = $ability;

        return $this;
    }

    public function registerEffect(Effect $effect): static
    {
        $this->effects[$effect->key()] = $effect;

        return $this;
    }

    public function registerStrategy(AiStrategy $strategy): static
    {
        $this->strategies[$strategy->key()] = $strategy;

        return $this;
    }

    public function registerEnemy(EnemyTemplate $enemy): static
    {
        $this->enemies[$enemy->key()] = $enemy;

        return $this;
    }

    public function registerEncounter(Encounter $encounter): static
    {
        $this->encounters[$encounter->key()] = $encounter;

        return $this;
    }

    public function ability(string $key): Ability
    {
        return $this->abilities[$key] ?? throw new GameException("Unknown combat ability: {$key}");
    }

    public function effect(string $key): Effect
    {
        return $this->effects[$key] ?? throw new GameException("Unknown combat effect: {$key}");
    }

    public function strategy(string $key): AiStrategy
    {
        return $this->strategies[$key] ?? throw new GameException("Unknown combat strategy: {$key}");
    }

    public function enemy(string $key): EnemyTemplate
    {
        return $this->enemies[$key] ?? throw new GameException("Unknown enemy: {$key}");
    }

    public function encounter(string $key): Encounter
    {
        return $this->encounters[$key] ?? throw new GameException("Unknown encounter: {$key}");
    }

    public function hasAbility(string $key): bool
    {
        return isset($this->abilities[$key]);
    }

    public function hasEffect(string $key): bool
    {
        return isset($this->effects[$key]);
    }

    public function hasStrategy(string $key): bool
    {
        return isset($this->strategies[$key]);
    }

    public function hasEncounter(string $key): bool
    {
        return isset($this->encounters[$key]);
    }

    /** @return array<string, Ability> */
    public function abilities(): array
    {
        return $this->abilities;
    }

    /** @return array<string, Effect> */
    public function effects(): array
    {
        return $this->effects;
    }

    /** @return array<string, AiStrategy> */
    public function strategies(): array
    {
        return $this->strategies;
    }

    /** @return array<string, Encounter> */
    public function encounters(): array
    {
        return $this->encounters;
    }
}
