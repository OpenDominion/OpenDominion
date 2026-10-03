<?php

namespace OpenDominion\HeroCombat\Contracts;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Victory\VictoryCondition;
use OpenDominion\HeroCombat\Engine\EncounterContext;

interface Encounter
{
    public function key(): string;

    public function name(): string;

    public function source(): string;

    /**
     * Enemies to spawn on the opposing team.
     *
     * @return array<int, array{template: string, name?: string, stats?: array<string, int>, effects?: array<string, array<string, mixed>>}>
     */
    public function roster(EncounterContext $context): array;

    /**
     * Extra ability keys granted to a player combatant for this encounter.
     *
     * @return string[]
     */
    public function playerGrants(CombatantState $player, array $heroClasses): array;

    public function victoryCondition(): VictoryCondition;

    public function onTurnEnd(Battle $battle): void;

    /** Narrative line logged when the players' team wins, if any. */
    public function victoryMessage(): ?string;
}
