<?php

namespace OpenDominion\HeroCombat\Content;

use OpenDominion\HeroCombat\Contracts\Encounter;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Victory\LastTeamStanding;
use OpenDominion\HeroCombat\Engine\Victory\VictoryCondition;

abstract class AbstractEncounter implements Encounter
{
    public function source(): string
    {
        return '';
    }

    public function playerGrants(CombatantState $player, array $heroClasses): array
    {
        return [];
    }

    public function victoryCondition(): VictoryCondition
    {
        return new LastTeamStanding();
    }

    public function onTurnEnd(Battle $battle): void
    {
    }

    public function victoryMessage(): ?string
    {
        return null;
    }
}
