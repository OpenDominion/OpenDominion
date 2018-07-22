<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class RaceHelper
{
    public function getRaceDescriptionHtml(Race $race): string
    {
        return [

                'human' => '<p>foo</p><p>bar</p>',

                'nomad' => '',

                'dwarf' => '',

                'goblin' => '',

            ][strtolower($race->name)] ?: 'todo';
    }
}
