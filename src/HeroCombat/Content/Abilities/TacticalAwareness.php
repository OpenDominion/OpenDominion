<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Engine\Stats\Stat;

class TacticalAwareness extends AbstractWeakenStat
{
    public function key(): string
    {
        return 'tactical_awareness';
    }

    public function name(): string
    {
        return 'Tactical Awareness';
    }

    public function description(): string
    {
        return 'Reduces the target\'s counter value by 2 for the remainder of the battle.';
    }

    protected function stat(): Stat
    {
        return Stat::Counter;
    }

    protected function effectKey(): string
    {
        return 'outmaneuvered';
    }

    public function messages(): array
    {
        return parent::messages() + ['weaken' => '{actor} decreases {target}\'s counter value by 2.'];
    }
}
