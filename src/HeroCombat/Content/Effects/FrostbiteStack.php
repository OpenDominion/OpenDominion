<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class FrostbiteStack extends AbstractStatShift
{
    public function key(): string
    {
        return 'frostbitten';
    }

    public function name(): string
    {
        return 'Frostbitten';
    }

    public function tags(): array
    {
        return [CombatTag::Frost];
    }

    protected function stat(): Stat
    {
        return Stat::Defense;
    }

    protected function valuePerStack(): int
    {
        return -1;
    }
}
