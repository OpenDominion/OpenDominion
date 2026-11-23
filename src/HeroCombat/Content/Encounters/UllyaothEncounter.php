<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\EncounterContext;

/**
 * The first party fight: two heroes, one pilot. Blood Rain marks a hero for Devour; a partner
 * Guarding them halves it, while one hero drives the granted Titan Spear into the heart that
 * Devour exposes. Ullyaoth's Undying orc champion feeds it through Corpseblood Feast unless the
 * heroes defeat the champion on the Feast turn.
 */
class UllyaothEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'ullyaoth';
    }

    public function name(): string
    {
        return 'World-Eater Ullyaoth';
    }

    public function source(): string
    {
        return 'Raid (World-Eater)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'ullyaoth', 'name' => 'World-Eater Ullyaoth'],
            ['template' => 'bloodbound_orc', 'name' => 'Bloodbound Orc'],
        ];
    }

    public function playerGrants(CombatantState $player, array $heroClasses): array
    {
        return ['titan_spear'];
    }

    public function minPartySize(): int
    {
        return 2;
    }

    public function maxPartySize(): int
    {
        return 2;
    }

    public function victoryMessage(): ?string
    {
        return 'The Titan Spear finds the heart at last. Ullyaoth shrieks, and the half-formed dragon comes apart into a rain of blood that never reaches the ground.';
    }
}
