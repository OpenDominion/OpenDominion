<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Content\Abilities\AbstractAttack;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;

/**
 * An ordinary attack that also leaves the target Bleeding. Not twice in a row.
 */
class Hemorrhage extends AbstractAttack
{
    public function key(): string
    {
        return 'hemorrhage';
    }

    public function name(): string
    {
        return 'Hemorrhage';
    }

    public function description(): string
    {
        return 'A raking strike that leaves the target Bleeding.';
    }

    public function selectable(): bool
    {
        return false;
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 1;
    }

    public function resolve(ActionContext $context): void
    {
        foreach ($context->targets as $target) {
            $result = $context->attack($target);
            static::narrate($context, $result);
            $context->applyEffect($result->target, 'bleeding');
        }
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} rakes {target} for {damage} damage, opening a bleeding wound.',
            'evaded' => '{actor} rakes {target} for {raw} damage, but {target} twists away, reducing damage to {damage}.',
        ];
    }
}
