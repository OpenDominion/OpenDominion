<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;

class BladeFlurry extends AbstractAttack
{
    public const HITS = 2;
    public const MULTIPLIER = 0.75;

    public function key(): string
    {
        return 'blade_flurry';
    }

    public function name(): string
    {
        return 'Blade Flurry';
    }

    public function description(): string
    {
        return 'Attack twice for 75% damage each time.';
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 1;
    }

    protected function damageOptions(ActionContext $context): array
    {
        return ['hits' => self::HITS, 'multiplier' => self::MULTIPLIER];
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} unleashes a blade flurry, striking ' . self::HITS . ' times for {damage} damage to {target}.',
            'evaded' => '{actor} unleashes a blade flurry, striking ' . self::HITS . ' times for {raw} damage, but {target} evades, reducing damage to {damage}.',
        ];
    }
}
