<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;

class ShadowStrike extends AbstractAttack
{
    public function key(): string
    {
        return 'shadow_strike';
    }

    public function name(): string
    {
        return 'Shadow Strike';
    }

    public function description(): string
    {
        return 'Attack that cannot be evaded and deals +2 damage if the target is defending.';
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 1;
    }

    protected function damageOptions(ActionContext $context): array
    {
        return ['canEvade' => false, 'defendModifier' => -2];
    }

    public function messages(): array
    {
        return ['hit' => '{actor} strikes from the shadows, dealing {damage} damage to {target}.'];
    }
}
