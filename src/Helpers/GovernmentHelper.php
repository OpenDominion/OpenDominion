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
                'description' => 'Can appoint members of the court'
            ],
            [
                'name' => 'General',
                'key' => 'general',
                'icon' => 'ra ra-warlord',
                'icon-color' => 'primary',
                'description' => 'Can cancel and declare war'
            ],
            [
                'name' => 'Grand Magister',
                'key' => 'magister',
                'icon' => 'ra ra-wizard-face',
                'icon-color' => 'primary',
                'description' => 'Access to friendly spells'
            ],
            [
                'name' => 'Court Mage',
                'key' => 'mage',
                'icon' => 'ra ra-wizard-face',
                'icon-color' => 'primary',
                'description' => 'Access to friendly spells'
            ],
            [
                'name' => 'Court Jester',
                'key' => 'jester',
                'icon' => 'ra ra-jester-hat',
                'icon-color' => 'primary',
                'description' => 'Can change realm name and message'
            ]
        ]);
    }

    public function getCourtAppointment(string $key): array
    {
        return $this->getCourtAppointments()->keyBy('key')->get($key);
    }
}
