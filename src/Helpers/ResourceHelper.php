<?php

namespace OpenDominion\Helpers;


class ResourceHelper
{
    public function getInvasionResourceTypes(): array
    {
        return [
            'boats' => 'Boats',
            'gems' => 'Gems',
            'platinum' => 'Platinum',
            'prestige' => 'Prestige',
            'tech' => 'Research Points',
        ];
    }
}