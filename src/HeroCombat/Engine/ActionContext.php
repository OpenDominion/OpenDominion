<?php

namespace OpenDominion\HeroCombat\Engine;

use OpenDominion\HeroCombat\Contracts\Ability;
use OpenDominion\HeroCombat\Engine\Damage\DamageRequest;
use OpenDominion\HeroCombat\Engine\Damage\DamageResult;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Everything an ability needs while declaring or resolving.
 */
final class ActionContext
{
    /**
     * @param CombatantState[] $targets
     */
    public function __construct(
        public readonly Battle $battle,
        public readonly CombatantState $actor,
        public readonly Ability $ability,
        public readonly Intent $intent,
        public array $targets = [],
    ) {
    }

    public function target(): ?CombatantState
    {
        return $this->targets[0] ?? null;
    }

    public function dealDamage(DamageRequest $request): DamageResult
    {
        return $this->battle->damage->resolve($request);
    }

    /**
     * A standard attack from the actor using the attack-vs-defense formula.
     *
     * @param array<string, mixed> $options any DamageRequest constructor argument
     */
    public function attack(CombatantState $target, array $options = []): DamageResult
    {
        return $this->dealDamage(new DamageRequest(
            ...array_merge(['attacker' => $this->actor, 'target' => $target, 'abilityKey' => $this->ability->key()], $options),
        ));
    }

    /**
     * Fixed damage from the actor that ignores defense, evasion and counters.
     *
     * @param array<string, mixed> $options any DamageRequest constructor argument
     */
    public function flatDamage(CombatantState $target, int $amount, array $options = []): DamageResult
    {
        return $this->dealDamage(new DamageRequest(...array_merge([
            'attacker' => $this->actor,
            'target' => $target,
            'abilityKey' => $this->ability->key(),
            'flatDamage' => $amount,
            'canEvade' => false,
            'canBeCountered' => false,
        ], $options)));
    }

    public function heal(CombatantState $target, int $amount): int
    {
        return $this->battle->heal($target, $amount, $this->actor);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function applyEffect(CombatantState $target, string $key, ?int $turns = null, int $stacks = 1, array $data = []): ?EffectInstance
    {
        return $this->battle->effects->apply($target, $key, $turns, $stacks, $data, $this->actor);
    }

    public function removeEffectsTagged(CombatantState $target, CombatTag $tag): int
    {
        return $this->battle->effects->removeByTag($target, $tag);
    }

    public function summon(string $templateKey, ?string $name = null): CombatantState
    {
        return $this->battle->summon($templateKey, $this->actor->team, $name, $this->actor);
    }

    public function intentOf(CombatantState $combatant): ?Intent
    {
        return $this->battle->intentOf($combatant);
    }

    /**
     * Whether the combatant chose the given ability this turn.
     */
    public function chose(CombatantState $combatant, string $abilityKey): bool
    {
        return $this->intentOf($combatant)?->abilityKey === $abilityKey;
    }

    /**
     * Logs one of the ability's message templates.
     *
     * @param array<string, string|int> $variables
     */
    public function say(string $messageKey, array $variables = []): void
    {
        $template = $this->ability->messages()[$messageKey] ?? $messageKey;
        $this->battle->say($this->actor, self::format($template, $variables + [
            'actor' => $this->actor->name,
            'target' => $this->target()?->name ?? '',
        ]));
    }

    /**
     * @param array<string, string|int> $variables
     */
    public static function format(string $template, array $variables): string
    {
        $replacements = [];
        foreach ($variables as $name => $value) {
            $replacements['{' . $name . '}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }
}
