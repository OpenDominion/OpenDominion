<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;

class EspionageHelper
{
    public function getOperationInfo(string $operationKey): array
    {
        return [];
    }

    public function isInfoGatheringOperation(string $operationKey): bool
    {
        return false;
    }

    public function isResourceTheftOperation(string $operationKey): bool
    {
        return false;
    }

    public function isBlackOperation(string $operationKey): bool
    {
        return false;
    }

    public function isWarOperation(string $operationKey): bool
    {
        return false;
    }

    public function getInfoGatheringOperations(): Collection
    {
        return collect([
            [
                'name' => 'Barracks Spy',
                'description' => 'Reveal units',
                'key' => 'barracks_spy',
            ],
            [
                'name' => 'Castle Spy',
                'description' => 'Reveal castle improvements',
                'key' => 'castle_spy',
            ],
            [
                'name' => 'Survey Dominion',
                'description' => 'Reveal buildings',
                'key' => 'survey_dominion',
            ],
            [
                'name' => 'Land Spy',
                'description' => 'Reveal land',
                'key' => 'land_spy',
            ],
        ]);
    }

    public function getResourceTheftOperations(): Collection
    {
        return collect([
            // steal platinum
            // steal lumber
            // steal mana
            // steal food
            // steal ore
            // steal gems
        ]);
    }

    public function getBlackOperations(): Collection
    {
        return collect([
            // assassinate draftees
        ]);
    }

    public function getWarOperations(): Collection
    {
        return collect([
            // assassinate wiards
            // magic snare
            // sabotage boats
        ]);
    }
}
