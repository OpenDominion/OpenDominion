<?php

return [
    /*
     * Combat loadout slots heroes can equip. Slots left out are shown as coming soon and
     * nothing in them reaches a battle. Values are LoadoutSlot values: technique, ability, gear.
     */
    'loadout_slots' => ['gear'],

    /*
     * Whether a hero's first finished PvP battle each round day grants flat experience.
     */
    'daily_pvp_experience' => false,
];
