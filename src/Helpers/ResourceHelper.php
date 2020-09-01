<?php

namespace OpenDominion\Helpers;


class ResourceHelper
{
    public function getInvasionResourceTypes(): array
    {
        return [
            'prestige' => 'Prestige',
            'tech' => 'Research Points',
            'boats' => 'Boats',
            'platinum' => 'Platinum',
            'gems' => 'Gems',
        ];
    }
}