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
            'spires' => '+%s%% offensive wizard power & mana production<br>+%s%% spell damage reduction',
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
            'spires' => "{$improvementName}: wizard power and mana bonuses max out at +60% base<br><br>Protection: spell damage reduction bonus is increased by 50%, maxes out at +50%, and cannot be modified by masonries",
            'harbor' => "{$improvementName}: food bonus maxes out at +60% base<br><br>Protection: boat bonuses are increased by 50%, max out at +50%, and cannot be modified by masonries",
        ];

        return $helpStrings[$improvementType] ?: null;
    }
}
