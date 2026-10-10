<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Engine\Stats\Stat;

class CombatAnalysis extends AbstractWeakenStat
{
    public function key(): string
    {
        return 'combat_analysis';
    }

    public function name(): string
    {
        return 'Combat Analysis';
    }

    public function description(): string
    {
        return 'Decreases the target\'s defense value by 1 for the remainder of the battle.';
    }

    protected function stat(): Stat
    {
        return Stat::Defense;
    }

    protected function effectKey(): string
    {
        return 'analyzed';
    }

    public function messages(): array
    {
        return parent::messages() + ['weaken' => '{actor} decreases {target}\'s defense value by 1.'];
    }
}
