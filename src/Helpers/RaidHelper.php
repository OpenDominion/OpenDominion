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
            'espionage' => 'array', // list of available operations [key => {name, strength_cost, points_awarded}]
            'exploration' => 'array', // list of available operation [key => {name, morale_cost, draftee_cost, points_awarded}]
            'hero' => [
                'name' => 'string',
                // combat stats
                // key => 'integer'
                'points_awarded' => 'integer',
            ],
            'invasion' => [
                'casualties' => 'float', // percentage of units lost
                // points_awarded is calculated dynamically based on damage dealt
            ],
            'investment' => 'array', // [key => {name, resource, amount, points_awarded}]
            'magic' => 'array', // [key => {name, mana_cost, strength_cost, points_awarded}]
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
