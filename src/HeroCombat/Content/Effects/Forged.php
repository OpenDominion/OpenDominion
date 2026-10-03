<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Forged extends AbstractStatShift
{
    public function key(): string
    {
        return 'forged';
    }

    public function name(): string
    {
        return 'Forged';
    }

    protected function stat(): Stat
    {
        return Stat::Attack;
    }

    protected function valuePerStack(): int
    {
        return 1;
    }
}
