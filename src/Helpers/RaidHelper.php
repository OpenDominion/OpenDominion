<?php

namespace OpenDominion\Helpers;

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

    public function getTacticBonusSchema(string $type): array
    {
        $schemas = [
            'race' => 'array', // [race => modifier]
            'tech' => 'array', // [tech => modifier]
            'unit' => 'array', // [unit_type => modifier]
            'hero_class' => 'array', // [class => modifier]
            'alignment' => 'array', // [alignment => modifier]
        ];

        return $schemas[$type] ?? [];
    }
}
