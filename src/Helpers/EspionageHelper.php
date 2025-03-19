<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;

class EspionageHelper
{
    public function getOperationInfo(string $operationKey): array
    {
        return $this->getOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->first();
    }

    public function isInfoGatheringOperation(string $operationKey): bool
    {
        return $this->getInfoGatheringOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isResourceTheftOperation(string $operationKey): bool
    {
        return $this->getResourceTheftOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isHostileOperation(string $operationKey): bool
    {
        return $this->getHostileOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isBlackOperation(string $operationKey): bool
    {
        return $this->getBlackOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function isWarOperation(string $operationKey): bool
    {
        return $this->getWarOperations()->filter(function ($operation) use ($operationKey) {
            return ($operation['key'] === $operationKey);
        })->isNotEmpty();
    }

    public function getOperations(): Collection
    {
        return $this->getInfoGatheringOperations()
            ->merge($this->getResourceTheftOperations())
            ->merge($this->getBlackOperations())
            ->merge($this->getWarOperations());
    }

    public function getInfoGatheringOperations(): Collection
    {
        return collect([
            [
                'name' => 'Barracks Spy',
                'description' => 'Reveal estimate units',
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
            [
                'name' => 'Steal Platinum',
                'description' => 'Steal platinum from target',
                'key' => 'steal_platinum',
            ],
            [
                'name' => 'Steal Food',
                'description' => 'Steal food from target',
                'key' => 'steal_food',
            ],
            [
                'name' => 'Steal Lumber',
                'description' => 'Steal lumber from target',
                'key' => 'steal_lumber',
            ],
            [
                'name' => 'Steal Mana',
                'description' => 'Steal mana from target',
                'key' => 'steal_mana',
            ],
            [
                'name' => 'Steal Ore',
                'description' => 'Steal ore from target',
                'key' => 'steal_ore',
            ],
            [
                'name' => 'Steal Gems',
                'description' => 'Steal gems from target',
                'key' => 'steal_gems',
            ],
        ]);
    }

    public function getHostileOperations(): Collection
    {
        return $this->getBlackOperations()
            ->merge($this->getWarOperations());
    }

    public function getBlackOperations(): Collection
    {
        return collect([
            [
                'name' => 'Assassinate Draftees',
                'description' => 'Kills 3.5% untrained draftees',
                'key' => 'assassinate_draftees',
                'attrs' => ['military_draftees'],
                'percentage' => 3.5,
            ],
        ]);
    }

    public function getWarOperations(): Collection
    {
        return collect([
            [
                'name' => 'Assassinate Wizards',
                'description' => 'Kills 1.5% wizards',
                'key' => 'assassinate_wizards',
                'attrs' => ['military_wizards'],
                'percentage' => 1.5,
            ],
            [
                'name' => 'Magic Snare',
                'description' => 'Reduces wizard strength by 3.5% (min 1.5)',
                'key' => 'magic_snare',
                'attrs' => ['wizard_strength'],
                'percentage' => 3.5,
            ],
            [
                'name' => 'Sabotage Boats',
                'description' => 'Destroys 2.3% boats',
                'key' => 'sabotage_boats',
                'attrs' => ['resource_boats'],
                'percentage' => 2.3,
            ],
            [
                'name' => 'Incite Chaos',
                'description' => 'Increases critical failure chance by 5%',
                'key' => 'incite_chaos',
                'attrs' => ['chaos'],
                'percentage' => 5,
            ]
        ]);
    }

    public function getRacialWarOperation(Race $race) {
        $raceName = $race->name;
        return $this->getRacialWarOperations()->filter(function ($spell) use ($raceName) {
            return $spell['races']->contains($raceName);
        })->first();
    }

    public function getRacialWarOperations(): Collection
    {
        return collect([
            [
                'name' => 'Assassinate Archmages',
                'description' => 'Kills 1% archmages',
                'key' => 'assassinate_archmages',
                'decreases' => ['military_archmages'],
                'percentage' => 1,
                'races' => collect(['Spirit']),
            ],
        ]);
    }
}
