<?php

namespace OpenDominion\Helpers;

class RaidHelper
{
    public function getTypes(): string
    {
        return collect([
            'espionage',
            'exploration',
            'invasion',
            'investment',
            'magic',
        ]);
    }
}
