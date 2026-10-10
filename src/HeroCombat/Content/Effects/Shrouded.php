<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Shrouded extends AbstractStatShift
{
    public function key(): string
    {
        return 'shrouded';
    }

    public function name(): string
    {
        return 'Shrouded';
    }

    protected function stat(): Stat
    {
        return Stat::Evasion;
    }

    protected function valuePerStack(): int
    {
        return 20;
    }
}
