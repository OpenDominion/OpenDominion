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

                'dwarf' => '<p>description here</p><p class="text-green">Increased ore production<br>Increased max population</p>',

                'goblin' => '<p>description here</p><p class="text-green">Increased gem production<br>Increased max population</p>',

            ][strtolower($race->name)] ?: 'todo';
    }
}
