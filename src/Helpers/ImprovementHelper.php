<?php

namespace OpenDominion\Helpers;

class ImprovementHelper
{
    public function getImprovementTypes(): array
    {
        return [
            'science',
            'keep',
            'forges',
            'walls',
            'spires',
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
            'forges' => '+%s%% offensive power',
            'walls' => '+%s%% defensive power',
            'spires' => '+%s%% offensive wizard power & mana production<br>+%s%% protection from spells',
            'harbor' => '+%s%% food production<br>+%s%% boat production & protection',
        ];

        return $ratingStrings[$improvementType] ?: null;
    }

    public function getImprovementHelpString(string $improvementType): string
    {
        $improvementName = $this->getImprovementName($improvementType);

        $helpStrings = [
            'science' => "{$improvementName}: platinum bonus maxes out at +20% base",
            'keep' => "{$improvementName}: population bonus maxes out at +30% base",
            'forges' => "{$improvementName}: offense bonus maxes out at +30% base",
            'walls' => "{$improvementName}: defense bonus maxes out at +30% base",
            'spires' => "{$improvementName}: wizard and mana bonuses max out at +60% base, spell protection bonus is decreased by 50% and maxes out at +30% and cannot be modified",
            'harbor' => "{$improvementName}: food bonus maxes out at +60% base, boat bonuses are increased by 25% and max out at +75%",
        ];

        return $helpStrings[$improvementType] ?: null;
    }
}
