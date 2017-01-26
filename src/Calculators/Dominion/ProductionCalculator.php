<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class ProductionCalculator extends AbstractDominionCalculator
{
    /** @var PopulationCalculator */
    protected $populationCalculator;

    /**
     * {@inheritDoc}
     */
    public function initDependencies()
    {
        $this->populationCalculator = app()->make(PopulationCalculator::class);
    }

    /**
     * {@inheritDoc}
     */
    public function init(Dominion $dominion)
    {
        parent::init($dominion);

        $this->populationCalculator->setDominion($dominion);

        return $this;
    }

    // Platinum

    /**
     * Returns the Dominion's platinum production.
     *
     * @return int
     */
    public function getPlatinumProduction()
    {
        return (int)floor($this->getPlatinumProductionRaw() * $this->getPlatinumProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw platinum production.
     *
     * @return float
     */
    public function getPlatinumProductionRaw()
    {
        $platinum = 0;

        // Values
        $peasantTax = 2.7;
        $spellAlchemistFlameBonus = 15;
        $platinumPerAlchemy = 45;

        // Peasant Tax
        $platinum += (($this->dominion->peasants * $peasantTax) * ($this->populationCalculator->getEmploymentPercentage() / 100));
        // todo? change to: $platinum += ($this->populationCalculator->getPopulationEmployed() * $peasantTax);
        // Now with 4k peasants and 2k jobs with 100% employment you'd get 4k * peasantTax plat
        // With the todo only for employed peasants, as advertised here: http://web.archive.org/web/20110512060054/http://dominion.lykanthropos.com/wiki/index.php/Population#Peasants

        // Spell: Alchemist Flame
        // todo

        // Alchemies
        $platinum += ($this->dominion->building_alchemy * $platinumPerAlchemy);

        /*
        = Population!C3 * Constants!$B$58 * Population!I3 + (Construction!AT4+Construction!BR4)*(Constants!$B$4+(Magic!AI3>0)*Constants!$F$83)
        */

        return (float)$platinum;
    }

    /**
     * Returns the Dominion's platinum production multiplier.
     *
     * @return float
     */
    public function getPlatinumProductionMultiplier()
    {
        $multiplier = 0.0;

        // Values (percentages)
        $spellMidasTouch = 10;
        $guardTax = -2;
        $techProduction = 5;
        $techTreasureHunt = 12.5;

        // Racial Bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('platinum_production');

        // Spell: Midas Touch
        // todo

        // Improvement: Science
        // todo

        // Guard Tax
        // todo

        // Tech: Production or Treasure Hunt
        // todo

        return (float)min(1.5, (1 + $multiplier));
    }

    // Food

    /**
     * Returns the Dominion's food production.
     *
     * @return int
     */
    public function getFoodProduction()
    {
        return (int)floor($this->getFoodProductionRaw() * $this->getFoodProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw food production.
     *
     * @return float
     */
    public function getFoodProductionRaw()
    {
        $food = 0;

        // Values
        $foodPerFarm = 80;
        $foodPerDock = 35;

        // Farms
        $food += ($this->dominion->building_farm * $foodPerFarm);

        // Docks
        $food += ($this->dominion->building_dock * $foodPerDock);

        return (float)$food;
    }

    /**
     * Returns the Dominion's food production multiplier.
     *
     * @return float
     */
    public function getFoodProductionMultiplier()
    {
        $multiplier = 0.0;

        // Values (percentages)
        $spellGaiasBlessing = 20;
        $spellGaiasWatch = 10;
        $techProduction = 10;

        // Racial Bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('food_production');

        // Spell: Gaia's Blessing
        // todo

        // Spell: Gaia's Watch
        // todo

        // Improvement: Irrigation
        // todo

        // Tech: Production
        // todo

        // Prestige bonus multiplier
        $multiplier *= (1 + (($this->dominion->prestige / 250) * 2.5) / 100);
        $multiplier += ((($this->dominion->prestige / 250) * 2.5) / 100);

        return (float)(1 + $multiplier);
    }

    /**
     * Returns the Dominion's food consumption.
     *
     * @return float
     */
    public function getFoodConsumption()
    {
        $consumption = 0;

        // Values
        $populationConsumption = 0.25;

        // Population consumption
        $consumption += ($this->populationCalculator->getPopulation() * $populationConsumption);

        // Racial bonus
        $consumption *= (1 + $this->dominion->race->getPerkMultiplier('food_consumption'));

        return (float)$consumption;
    }

    /**
     * Returns the Dominion's food decay.
     *
     * @return float
     */
    public function getFoodDecay()
    {
        $decay = 0;

        // Values (percentages)
        $foodDecay = 1;

        $decay += ($this->dominion->resource_food * ($foodDecay / 100));

        return (float)$decay;
    }

    /**
     * Returns the Dominion's net food change.
     *
     * @return int
     */
    public function getFoodNetChange()
    {
        return (int)round($this->getFoodProduction() - $this->getFoodConsumption() - $this->getFoodDecay());
    }

    // Lumber

    /**
     * Returns the Dominion's lumber production.
     *
     * @return int
     */
    public function getLumberProduction()
    {
        return (int)floor($this->getLumberProductionRaw() * $this->getLumberProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw lumber production.
     *
     * @return float
     */
    public function getLumberProductionRaw()
    {
        $lumber = 0;

        // Values
        $lumberPerLumberyard = 50;

        // Lumberyards
        $lumber += ($this->dominion->building_lumberyard * $lumberPerLumberyard);

        return (float)$lumber;
    }

    /**
     * Returns the Dominion's lumber production multiplier.
     *
     * @return float
     */
    public function getLumberProductionMultiplier()
    {
        $multiplier = 0.0;

        // Values (percentages)
        $spellGaiasBlessing = 10;
        $techProduction = 10;

        // Racial Bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('lumber_production');

        // Spell: Gaia's Blessing
        // todo

        // Tech: Production
        // todo

        return (float)(1 + $multiplier);
    }

    /**
     * Returns the Dominion's lumber decay.
     *
     * @return float
     */
    public function getLumberDecay()
    {
        $decay = 0;

        // Values (percentages)
        $lumberDecay = 1;

        $decay += ($this->dominion->resource_lumber * ($lumberDecay / 100));

        return (float)$decay;
    }

    /**
     * Returns the Dominion's net lumber change.
     *
     * @return int
     */
    public function getLumberNetChange()
    {
        return (int)round($this->getLumberProduction() - $this->getLumberDecay());
    }

    // Mana

    /**
     * Returns the Dominion's mana production.
     *
     * @return int
     */
    public function getManaProduction()
    {
        return (int)floor($this->getManaProductionRaw() * $this->getManaProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw mana production.
     *
     * @return float
     */
    public function getManaProductionRaw()
    {
        $mana = 0;

        // Values
        $manaPerTower = 25;

        // Towers
        $mana += ($this->dominion->building_tower * $manaPerTower);

        return (float)$mana;
    }

    /**
     * Returns the Dominion's mana production multiplier.
     *
     * @return float
     */
    public function getManaProductionMultiplier()
    {
        $multiplier = 0.0;

        // Values (percentages)

        // Racial Bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('mana_production');

        // Tech: Enchanted Lands (+15%)
        // todo

        return (float)(1 + $multiplier);
    }

    /**
     * Returns the Dominion's mana decay.
     *
     * @return float
     */
    public function getManaDecay()
    {
        $decay = 0;

        // Values (percentages)
        $manaDecay = 2;

        $decay += ($this->dominion->resource_mana * ($manaDecay / 100));

        return (float)$decay;
    }

    /**
     * Returns the Dominion's net mana change.
     *
     * @return int
     */
    public function getManaNetChange()
    {
        return (int)round($this->getManaProduction() - $this->getManaDecay());
    }

    // Ore

    /**
     * Returns the Dominion's ore production.
     *
     * @return int
     */
    public function getOreProduction()
    {
        return (int)floor($this->getOreProductionRaw() * $this->getOreProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw ore production.
     *
     * @return float
     */
    public function getOreProductionRaw()
    {
        $ore = 0;

        // Values
        $orePerOreMine = 60;

        // Ore Mines
        $ore += ($this->dominion->building_ore_mine * $orePerOreMine);

        // Dwarf Unit: Miner
        // todo

        return (float)$ore;
    }

    /**
     * Returns the Dominion's ore production multiplier.
     *
     * @return float
     */
    public function getOreProductionMultiplier()
    {
        $multiplier = 0.0;

        // Racial Bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('ore_production');

        // Magic: Miner's Sight (Dwarf) (+20%)
        // Magic: Mining Strength (+10%)
        // todo

        // Tech: Fruits of Labor (+20%)
        // todo

        return (float)(1 + $multiplier);
    }

    // Gems

    /**
     * Returns the Dominion's gem production.
     *
     * @return int
     */
    public function getGemProduction()
    {
        return (int)floor($this->getGemProductionRaw() * $this->getGemProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw gem production.
     *
     * @return float
     */
    public function getGemProductionRaw()
    {
        $gems = 0;

        // Values
        $gemsPerDiamondMine = 15;

        // Diamond Mines
        $gems += ($this->dominion->building_diamond_mine * $gemsPerDiamondMine);

        return (float)$gems;
    }

    /**
     * Returns the Dominion's gem production multiplier.
     *
     * @return float
     */
    public function getGemProductionMultiplier()
    {
        $multiplier = 0.0;

        // Racial Bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('gem_production');

        // Tech: Production (+5%)
        // Tech: Fruits of Labor (+15%)
        // todo

        return (float)(1 + $multiplier);
    }

    // Tech
    // todo

    // Boats

    /**
     * Returns the Dominion's boat production.
     *
     * @return int
     */
    public function getBoatProduction()
    {
        $boats = 0;

        // Values
        $docksPerBoat = 20;

        // todo: store boats as float? i.e +1 boat every 20 hours with 1 dock
        $boats += floor($this->dominion->building_dock / $docksPerBoat);

        return (int)$boats;
    }
}
