<?php

namespace OpenDominion\Calculators\Dominion;

class EmploymentCalculator extends AbstractDominionCalculator
{
    public function getEmploymentJobs()
    {
        return (20 * (
            $this->dominion->building_alchemy
            + $this->dominion->building_farm
//            + $this->dominion->building_smithy
//            + $this->dominion->building_masonry
//            + $this->dominion->building_ore_mine
//            + $this->dominion->building_gryphon_nest
//            + $this->dominion->building_tower
//            + $this->dominion->building_wizard_guild
//            + $this->dominion->building_temple
//            + $this->dominion->building_diamond_mine
//            + $this->dominion->building_school
            + $this->dominion->building_lumberyard
//            + $this->dominion->building_forest_haven
//            + $this->dominion->building_factory
//            + $this->dominion->building_guard_tower
//            + $this->dominion->building_shrine
//            + $this->dominion->building_dock
        ));
    }

    public function getPopulationEmployed()
    {
        return min($this->getEmploymentJobs(), $this->dominion->peasants);
    }

    public function getEmploymentPercentage()
    {
        return (min(1, ($this->getPopulationEmployed() / $this->dominion->peasants)) * 100);
    }
}
