<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Content\Effects\AbsorbedSouls;
use OpenDominion\HeroCombat\Engine\ActionContext;

/**
 * Waaugh's everyday spell: light flat damage that grows with his absorbed souls.
 */
class Hexfire extends AbstractAbility
{
    public const DAMAGE = 10;

    public function key(): string
    {
        return 'hexfire';
    }

    public function name(): string
    {
        return 'Hexfire';
    }

    public function description(): string
    {
        return 'A bolt of green-black fire that ignores defense.';
    }

    public function selectable(): bool
    {
        return false;
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();
        if ($target === null) {
            return;
        }

        $result = $context->flatDamage($target, self::DAMAGE + AbsorbedSouls::bonusDamage($context->actor, $context->battle));
        $context->say('hit', ['damage' => $result->amount + $result->absorbed]);
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} casts Hexfire. A bolt of green-black fire licks across the battlefield and scorches {target} for {damage} damage.',
        ];
    }
}
