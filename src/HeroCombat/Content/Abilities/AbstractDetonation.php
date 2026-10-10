<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

/**
 * Identical damage to every enemy, ignoring defense, evasion, shields and lethal saves.
 */
abstract class AbstractDetonation extends AbstractAbility
{
    abstract protected function multiplier(): float;

    public function targetRule(): TargetRule
    {
        return TargetRule::AllEnemies;
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 1;
    }

    public function tags(): array
    {
        return [CombatTag::Attack, CombatTag::AreaOfEffect];
    }

    public function resolve(ActionContext $context): void
    {
        if ($context->targets === []) {
            $context->say('no_targets');
            return;
        }

        $damage = 0;
        foreach ($context->targets as $target) {
            $result = $context->attack($target, [
                'multiplier' => $this->multiplier(),
                'ignoreDefense' => true,
                'canEvade' => false,
                'canBeCountered' => false,
                'ignoreShield' => true,
                'bypassLethalSave' => true,
            ]);
            $damage = max($damage, $result->amount);
        }

        $context->say('hit', ['damage' => $damage]);
    }
}
