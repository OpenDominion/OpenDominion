<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;

class GovernmentHelper
{
    public function getCourtAppointments(): Collection
    {
        return collect([
            [
                'name' => 'Monarch',
                'key' => 'monarch',
                'icon' => 'ra ra-queen-crown',
                'icon-color' => 'red',
                'description' => 'Can appoint members of the court',
                'perk_type' => 'food_production',
                'perk_value' => 2
            ],
            [
                'name' => 'General',
                'key' => 'general',
                'icon' => 'ra ra-warlord-helmet',
                'icon-color' => 'light-blue',
                'description' => 'Can cancel and declare war',
                'perk_type' => 'player_prestige_gains',
                'perk_value' => 2
            ],
            [
                'name' => 'Spymaster',
                'key' => 'spymaster',
                'icon' => 'ra ra-plain-dagger',
                'icon-color' => 'light-blue',
                'description' => 'Can manage the bounty board',
                'perk_type' => 'espionage_chance',
                'perk_value' => 5
            ],
            [
                'name' => 'Grand Magister',
                'key' => 'magister',
                'icon' => 'ra ra-winged-scepter',
                'icon-color' => 'light-blue',
                'description' => 'Access to friendly spells',
                'perk_type' => 'spell_cost',
                'perk_value' => -2.5
            ],
            [
                'name' => 'Court Mage',
                'key' => 'mage',
                'icon' => 'ra ra-wizard-face',
                'icon-color' => 'light-blue',
                'description' => 'Access to friendly spells',
                'perk_type' => 'wizard_strength_recovery',
                'perk_value' => 0.2
            ],
            [
                'name' => 'Court Jester',
                'key' => 'jester',
                'icon' => 'ra ra-jester-hat',
                'icon-color' => 'light-blue',
                'description' => 'Can change realm name and message',
                'perk_type' => 'morale_regen',
                'perk_value' => 1
            ]
        ])->keyBy('key');;
    }

    public function getCourtAppointment(string $key): array
    {
        return $this->getCourtAppointments()->keyBy('key')->get($key);
    }

    public function getCourtPerkHelpString(string $key): string
    {
        $appointment = $this->getCourtAppointments()[$key];

        $helpStrings = [
            'espionage_chance' => '%+g%% spy operation success chance',
            'food_production' => '%+g%% food production',
            'morale_regen' => '%+g morale regen per hour',
            'player_prestige_gains' => '%+g prestige gained against players',
            'spell_cost' => '%+g%% cost of spells',
            'wizard_strength_recovery' => '%+.g%% wizard strength per hour',
        ];

        return sprintf($helpStrings[$appointment['perk_type']], $appointment['perk_value']);
    }
}
