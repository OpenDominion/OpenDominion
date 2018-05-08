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
            'harbor',
        ];
    }

    public function getImprovementRatingString(string $improvementType): string
    {
        $ratingStrings = [
            'science' => '+%s%% platinum production',
            'keep' => '+%s%% max population',
            'towers' => '+%1$s%% wizard power, -%1$s%% damage from spells',
            'forges' => '+%s%% offensive power',
            'walls' => '+%s%% defensive power',
            'harbor' => '+%s%% food production, boat production & protection',
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
            'harbor' => 'Improvements to your harbor improve your food production, boat production and boat protection.<br><br>Max +40% base harbor.',
        ];

        return $helpStrings[$improvementType] ?: null;
    }

    // temp
    public function getImprovementImplementedString(string $improvementType): ?string
    {
        if ($improvementType === 'towers') {
            return '<abbr title="Partially implemented" class="label label-warning">PI</abbr>';
        }

        return null;
    }
}
