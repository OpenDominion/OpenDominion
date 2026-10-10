<?php

namespace OpenDominion\HeroCombat\Engine\Victory;

use OpenDominion\HeroCombat\Engine\Battle;

interface VictoryCondition
{
    /** Returns null while the battle should continue. */
    public function evaluate(Battle $battle): ?VictoryResult;
}
