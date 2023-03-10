<?php

namespace OpenDominion\Helpers;

class ImprovementHelper
{
    public function getImprovementTypes(): array
    {
        return [
            'science',
            'keep',
            'spires',
            'forges',
            'walls',
            'harbor',
        ];
    }

    public function getImprovementName(string $improvementType): string
    {
        return ucwords(str_replace('_', ' ', $improvementType));
    }

    public function getImprovementRatingString(string $improvementType): string
    {
        $ratingStrings = [
            'science' => '+%s%% platinum production',
            'keep' => '+%s%% max population',
            'spires' => '+%s%% offensive wizard power, mana production, spell damage reduction',
            'forges' => '+%s%% offensive power',
            'walls' => '+%s%% defensive power',
            'harbor' => '+%s%% food production, +%s%% boat production & protection',
        ];

        return $ratingStrings[$improvementType] ?: null;
    }

    public function getImprovementHelpString(string $improvementType): string
    {
        $improvementName = $this->getImprovementName($improvementType);

        $helpStrings = [
            'science' => "Improvements to {$improvementName} increase your platinum production.<br><br>Max +20% base {$improvementName}.",
            'keep' => "Improvements to your {$improvementName} increase your maximum population.<br><br>Max +30% base {$improvementName}.",
            'spires' => "Improvements to your {$improvementName} increase your offensive wizard power, mana production, and reduce damage from harmful spells.<br><br>Max +60% base {$improvementName}.",
            'forges' => "Improvements to your {$improvementName} increase your offensive power.<br><br>Max +30% base {$improvementName}.",
            'walls' => "Improvements to your {$improvementName} increase your defensive power.<br><br>Max +30% base {$improvementName}.",
            'harbor' => "Improvements to your {$improvementName} improve your food production, boat production, and boat protection.<br><br>Max +60% base {$improvementName}.",
        ];

        return $helpStrings[$improvementType] ?: null;
    }
}
