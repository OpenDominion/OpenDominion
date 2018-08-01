<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class RaceHelper
{
    public function getRaceDescriptionHtml(Race $race): string
    {
        return [

                'human' => '<p>description here</p><p class="text-green">Increased food production</p>',

                'nomad' => '<p>description here</p><p class="text-green">Increased mana production</p>',

                'dwarf' => '<p>description here</p><p class="text-green">Increased max population<br>Increased ore production</p>',

                'goblin' => '<p>description here</p><p class="text-green">Increased max population<br>Increased gem production<br>Improved castle bonuses</p>',

            ][strtolower($race->name)] ?: 'todo';
    }
}
