<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Outmaneuvered extends AbstractStatShift
{
    public function key(): string
    {
        return 'outmaneuvered';
    }

    public function name(): string
    {
        return 'Outmaneuvered';
    }

    protected function stat(): Stat
    {
        return Stat::Counter;
    }

    protected function valuePerStack(): int
    {
        return -2;
    }
}
