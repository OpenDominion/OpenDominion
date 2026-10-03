<?php

namespace OpenDominion\HeroCombat\Engine\Victory;

use OpenDominion\HeroCombat\Engine\Battle;

final class LastTeamStanding implements VictoryCondition
{
    public function evaluate(Battle $battle): ?VictoryResult
    {
        $livingTeams = array_unique(array_map(fn ($combatant) => $combatant->team, $battle->living()));

        if (count($livingTeams) === 0) {
            return new VictoryResult(null);
        }

        if (count($livingTeams) === 1) {
            return new VictoryResult(reset($livingTeams));
        }

        return null;
    }
}
