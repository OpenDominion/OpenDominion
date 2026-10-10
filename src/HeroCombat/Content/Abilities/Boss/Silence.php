<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;

class Silence extends AbstractAbility
{
    public function key(): string
    {
        return 'silence';
    }

    public function name(): string
    {
        return 'Silence';
    }

    public function description(): string
    {
        return 'The target cannot Focus or Recover for the next two turns.';
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 3;
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();
        if ($target === null) {
            return;
        }

        $context->applyEffect($target, 'silenced');
        $context->say('silence');
    }

    public function messages(): array
    {
        return ['silence' => '{actor} speaks a word of binding, and {target}\'s thoughts fall still.'];
    }
}
