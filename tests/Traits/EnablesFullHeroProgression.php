<?php

namespace OpenDominion\Tests\Traits;

use OpenDominion\HeroCombat\Content\Loadouts\LoadoutSlot;

/**
 * Turns on every hero progression feature that config may hold back (all loadout slots and
 * daily PvP experience), for tests of those features themselves.
 */
trait EnablesFullHeroProgression
{
    protected function enableFullHeroProgression(): void
    {
        config([
            'heroes.loadout_slots' => array_map(fn (LoadoutSlot $slot) => $slot->value, LoadoutSlot::cases()),
            'heroes.daily_pvp_experience' => true,
        ]);
    }
}
