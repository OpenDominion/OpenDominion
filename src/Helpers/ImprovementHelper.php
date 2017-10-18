<?php

namespace OpenDominion\Helpers;

class ImprovementHelper
{
    public function getImprovementTypes(): array
    {
        return [
            'science',
            'keep',
            'towers',
            'forges',
            'walls',
            'irrigation',
        ];
    }

    public function getImprovementRatingString(string $improvementType): string
    {
        $ratingStrings = [
            'science' => '+%s%% income',
            'keep' => '+%s%% max population',
            'towers' => '+%s%% wizard power',
            'forges' => '+%s%% offensive power',
            'walls' => '+%s%% defensive power',
            'irrigation' => '+%s%% food production',
        ];

        return $ratingStrings[$improvementType] ?: null;
    }

    public function getImprovementHelpString(string $improvementType): string
    {
        $helpStrings = [
            'science' => 'Improvements to science increase your platinum production.<br><br>Max +20% base science. Global platinum production bonus cannot exceed +50%.',
            'keep' => 'Improvements to your keep increase your maximum population.<br><br>Max +30% base keep.',
            'towers' => 'Improvements to your towers increase your wizard strength and reduce damage from harmful spells.<br><br>Max +40% base towers.',
            'forges' => 'Improvements to your forges increase your offensive power.<br><br>Max +30% base forges.',
            'walls' => 'Improvements to your walls increase your defensive power.<br><br>Max +30% base walls.',
            'irrigation' => 'Improvements to your harbors and irrigation improve your food production, boat production and boat protection.<br><br>Max +40% base irrigation.',
        ];

        return $helpStrings[$improvementType] ?: null;
    }

    // temp
    public function getImprovementImplementedString(string $improvementType): ?string
    {
        if ($improvementType === 'towers') {
            return '<abbr title="Not yet implemented" class="label label-danger">NYI</abbr>';
        }

        return null;
    }
}
