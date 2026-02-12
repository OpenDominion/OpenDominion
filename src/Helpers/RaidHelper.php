<?php

namespace OpenDominion\Helpers;

use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Models\Race;

class RaidHelper
{
    public function getTypes(): array
    {
        return [
            'espionage',
            'exploration',
            'hero',
            'invasion',
            'investment',
            'magic',
        ];
    }

    public function getStatusLabel(string $status, bool $objective = false): string
    {
        $labelClass = 'default';
        $labelText = $status;

        switch ($status) {
            case 'Upcoming':
                $labelClass = 'info';
                break;
            case 'In Progress':
                $labelClass = 'primary';
                break;
            case 'Completed':
                $labelClass = 'success';
                break;
            case 'Partial':
                $labelClass = 'warning';
                break;
            case 'Ended':
                $labelClass = 'danger';
                if ($objective) {
                    $labelText = 'Failed';
                }
                break;
            default:
                break;
        }

        return sprintf(
            '<span class="label label-%s">%s</span>',
            $labelClass,
            $labelText
        );
    }

    public function getTacticAttributeSchema(string $type): array
    {
        $schemas = [
            'espionage' => [
                'strength_cost' => 15,
                'morale_cost' => 10,
                'points_awarded' => 2,
                'limit' => 10,
            ],
            'exploration' => [
                'draftee_cost' => 1000,
                'morale_cost' => 10,
                'points_awarded' => 6000,
                'limit' => 10,
            ],
            'hero' => [
                'name' => 'Boss Name',
                'encounter' => 'encounter_key',
                'points_awarded' => 5000,
            ],
            'invasion' => [
                'casualties' => 3.5,
            ],
            'investment' => [
                'resource' => 'resource_ore',
                'amount' => 10000,
                'points_awarded' => 10000,
                'limit' => 10,
            ],
            'magic' => [
                'mana_cost' => 1.5,
                'strength_cost' => 10,
                'points_awarded' => 2,
                'limit' => 10,
            ],
        ];

        return $schemas[$type] ?? [];
    }

    public function getTacticBonusSchema(string $type = null): array
    {
        $schemas = [
            'race' => 'array', // [race => modifier]
            'tech' => 'array', // [tech => modifier]
            'unit' => 'array', // [unit_type => modifier]
            'hero_class' => 'array', // [class => modifier]
            'alignment' => 'array', // [alignment => modifier]
            'daily_ranking' => 'array', // [ranking_key => modifier] - applies when dominion holds rank 1
        ];

        return $type !== null ? ($schemas[$type] ?? []) : $schemas;
    }

    /**
     * Get valid bonus options for admin UI display.
     */
    public function getValidBonusOptions(): array
    {
        $heroHelper = app(HeroHelper::class);
        $rankingsHelper = app(RankingsHelper::class);

        return [
            'hero_classes' => $heroHelper->getClasses()->pluck('key')->toArray(),
            'alignments' => ['good', 'evil'],
            'daily_rankings' => $rankingsHelper->getRankings()->pluck('key')->toArray(),
        ];
    }

    /**
     * Get formatted bonus descriptions for a tactic.
     * Returns an array of human-readable bonus strings for display.
     */
    public function getTacticBonusDescription(array $bonuses): ?string
    {
        if (empty($bonuses)) {
            return null;
        }

        $descriptions = [];

        // Race bonuses
        if (isset($bonuses['race'])) {
            foreach ($bonuses['race'] as $raceKey => $multiplier) {
                $race = Race::where('key', $raceKey)->first();
                $percent = ($multiplier - 1) * 100;
                $descriptions[] = sprintf('%+g%% for %s race', $percent, $race->name);
            }
        }

        // Hero class bonuses
        if (isset($bonuses['hero_class'])) {
            $heroHelper = app(HeroHelper::class);
            foreach ($bonuses['hero_class'] as $class => $multiplier) {
                $percent = ($multiplier - 1) * 100;
                $className = $heroHelper->getClasses()->has($class)
                    ? $heroHelper->getClasses()[$class]['name']
                    : ucfirst($class);
                $descriptions[] = sprintf('%+g%% for %s hero', $percent, $className);
            }
        }

        // Alignment bonuses
        if (isset($bonuses['alignment'])) {
            foreach ($bonuses['alignment'] as $alignment => $multiplier) {
                $percent = ($multiplier - 1) * 100;
                $descriptions[] = sprintf('+%g%% for %s alignment', $percent, ucfirst($alignment));
            }
        }

        // Tech bonuses
        if (isset($bonuses['tech'])) {
            $techHelper = app(TechHelper::class);
            $techs = $techHelper->getTechs();
            foreach ($bonuses['tech'] as $techKey => $multiplier) {
                $percent = ($multiplier - 1) * 100;
                $techName = $techs->has($techKey) ? $techs[$techKey]->name : $techKey;
                $descriptions[] = sprintf('%+g%% for %s tech', $percent, $techName);
            }
        }

        // Daily ranking bonuses
        if (isset($bonuses['daily_ranking'])) {
            $rankingsHelper = app(RankingsHelper::class);
            $rankings = $rankingsHelper->getRankings();
            foreach ($bonuses['daily_ranking'] as $rankingKey => $multiplier) {
                $ranking = $rankings[$rankingKey];
                $percent = ($multiplier - 1) * 100;
                $descriptions[] = sprintf('%+g%% for %s title', $percent, ucfirst($ranking['title']));
            }
        }

        return implode(', ', $descriptions);
    }
}
