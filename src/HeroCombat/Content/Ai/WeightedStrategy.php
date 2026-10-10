<?php

namespace OpenDominion\HeroCombat\Content\Ai;

use OpenDominion\HeroCombat\Contracts\Ability;
use OpenDominion\HeroCombat\Contracts\AiStrategy;
use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Intent;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

/**
 * Picks a random usable ability by weight, with a few survival heuristics.
 */
class WeightedStrategy implements AiStrategy
{
    public const LOW_HEALTH_THRESHOLD = 40;

    /**
     * @param array<string, int> $weights ability key => weight
     */
    public function __construct(
        protected string $key,
        protected string $name,
        protected array $weights,
        protected bool $playerSelectable = false,
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function playerSelectable(): bool
    {
        return $this->playerSelectable;
    }

    /**
     * @return array<string, int>
     */
    public function weights(): array
    {
        return $this->weights;
    }

    public function chooseIntent(CombatantState $actor, Battle $battle): Intent
    {
        $validator = new ActionValidator($battle);
        $options = [];

        foreach ($this->weights as $abilityKey => $weight) {
            if ($validator->canPerform($actor, $abilityKey)) {
                $options[$abilityKey] = $weight;
            }
        }

        if ($battle->hasTag($actor, CombatTag::Shielded)) {
            unset($options['fortify']);
        }

        if ($battle->maxHealth($actor) < $actor->currentHealth + $battle->stat($actor, Stat::Recover)) {
            unset($options['recover']);
        }

        if ($actor->currentHealth <= self::LOW_HEALTH_THRESHOLD && isset($options['recover'])) {
            unset($options['focus']);
            $options['recover'] = ($this->weights['attack'] ?? 1) * 2;
        }

        $options = array_filter($options, fn ($weight) => $weight > 0);

        if ($options === []) {
            $abilityKey = $validator->canPerform($actor, 'attack') ? 'attack' : ActionValidator::PASS;
        } else {
            $abilityKey = $battle->random->weighted($options);
        }

        $target = $this->chooseTarget($actor, $battle->registry->ability($abilityKey), $battle);

        return new Intent($actor->id, $abilityKey, $target?->id, Intent::SOURCE_AI);
    }

    /**
     * Hostile single-target abilities leave the target open (a random enemy is chosen at
     * resolution); ally abilities pick the most wounded ally.
     */
    protected function chooseTarget(CombatantState $actor, Ability $ability, Battle $battle): ?CombatantState
    {
        if (!in_array($ability->targetRule(), [TargetRule::SingleAlly, TargetRule::SingleAny], true)) {
            return null;
        }

        $allies = $battle->alliesOf($actor);
        usort($allies, fn (CombatantState $a, CombatantState $b) => ($a->currentHealth / max(1, $battle->maxHealth($a))) <=> ($b->currentHealth / max(1, $battle->maxHealth($b))));

        return $allies[0] ?? $actor;
    }

    /**
     * @return static[]
     */
    public static function presets(): array
    {
        return [
            new static('balanced', 'Balanced', ['attack' => 4, 'defend' => 1, 'focus' => 1, 'counter' => 1, 'recover' => 1], true),
            new static('aggressive', 'Aggressive', ['attack' => 5, 'focus' => 3, 'counter' => 1, 'recover' => 1], true),
            new static('defensive', 'Defensive', ['attack' => 3, 'defend' => 1, 'counter' => 1, 'recover' => 1], true),
            new static('attack', 'Mindless Attacker', ['attack' => 1]),
            new static('counter', 'Counter-Heavy', ['attack' => 3, 'defend' => 1, 'counter' => 3, 'recover' => 1]),
            new static('pirate', 'Pirate', ['attack' => 2, 'blade_flurry' => 3, 'focus' => 1, 'counter' => 1, 'recover' => 1]),
            new static('summoner', 'Summoner', ['attack' => 0, 'defend' => 4, 'recover' => 1]),
            new static('fortify', 'Fortify', ['attack' => 3, 'fortify' => 3, 'counter' => 2]),
            new static('wraith', 'Wraith', ['attack' => 3, 'counter' => 3, 'recover' => 1, 'focus' => 1]),
            new static('warchief', 'Warchief', ['attack' => 3, 'counter' => 4, 'focus' => 2, 'recover' => 1]),
            new static('noctis', 'Noctis', ['attack' => 3, 'counter' => 2, 'focus' => 1]),
        ];
    }
}
