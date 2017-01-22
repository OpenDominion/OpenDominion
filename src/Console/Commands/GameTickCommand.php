<?php

namespace OpenDominion\Console\Commands;

use Carbon\Carbon;
use Config;
use DB;
use Exception;
use Illuminate\Console\Command;
use Log;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Repositories\DominionRepository;

class GameTickCommand extends Command
{
    /** @var string The name and signature of the console command */
    protected $signature = 'game:tick';

    /** @var string The console command description */
    protected $description = 'Ticks the game';

    /** @var string */
    protected $databaseDriver;

    /** @var DominionRepository */
    protected $dominions;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var ProductionCalculator */
    protected $productionCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /**
     * GameTickCommand constructor.
     *
     * @param DominionRepository $dominions
     */
    public function __construct(DominionRepository $dominions)
    {
        parent::__construct();

        $this->dominions = $dominions;
        $this->populationCalculator = app()->make(PopulationCalculator::class);
        $this->productionCalculator = app()->make(ProductionCalculator::class);
        $this->networthCalculator = app()->make(NetworthCalculator::class);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setDatabaseDriver();

        $this->initCalculatorDependencies();

        Log::debug('Tick started');

        DB::beginTransaction();

        // todo: below only for current active rounds

        $this->tickDominionResources();
        // todo: Population (peasants & draftees)
        $this->tickDominionMorale();
        $this->tickDominionSpyStrength();
        $this->tickDominionWizardStrength();
        $this->tickExplorationQueue();
        $this->tickConstructionQueue();
        // todo: Military training queue
        // todo: Military returning queue
        // todo: Magic queue
        $this->tickDominionNetworth();

        DB::commit();

        Log::info('Ticked');
    }

    public function setDatabaseDriver()
    {
        $connector = Config::get('database.default');
        $driver = Config::get("database.connections.{$connector}.driver");

        if (!in_array($driver, ['mysql', 'sqlite'])) {
            throw new Exception("Database driver {$driver} not supported for game:tick command :(");
        }

        $this->databaseDriver = $driver;
    }

    public function tickDominionResources()
    {
        Log::debug('Tick resources started');

        $dominions = $this->dominions->all();

        foreach ($dominions as $dominion) {
            /** @var $dominion Dominion */
            $this->initCalculatorsForDominion($dominion);

            // Resources
            $dominion->resource_platinum += $this->productionCalculator->getPlatinumProduction();
            $dominion->resource_food += $this->productionCalculator->getFoodNetChange();
            // todo: if food < 0 then food = 0?
            $dominion->resource_lumber += $this->productionCalculator->getLumberNetChange();
            // todo: if lumber < 0 then lumber = 0?
            $dominion->resource_mana += $this->productionCalculator->getManaNetChange();
            // todo: if mana < 0 then mana = 0?
            // todo: ore
            // todo: gems
            $dominion->resource_boats += $this->productionCalculator->getBoatProduction();

            // Population
            $populationPeasantGrowth = $this->populationCalculator->getPopulationPeasantGrowth();

            $dominion->peasants += $populationPeasantGrowth;
            $dominion->peasants_last_hour = $populationPeasantGrowth;
            $dominion->military_draftees += $this->populationCalculator->getPopulationDrafteeGrowth();

            $dominion->save();
        }

        $affected = $dominions->count();

        Log::debug("Ticked resources, {$affected} dominion(s) affected");
    }

    public function tickDominionMorale()
    {
        Log::debug('Tick morale started');

        $sql = null;
        $bindings = [
            'moraleThreshold' => 70,
            'moraleAddedBelowThreshold' => 6,
            'moraleAddedAboveThreshold' => 3,
        ];

        switch ($this->databaseDriver) {
            case 'sqlite':
                $sql = 'UPDATE `dominions` SET `morale` = MIN(100, (`morale` + (CASE WHEN (`morale` < :moraleThreshold) THEN :moraleAddedBelowThreshold ELSE :moraleAddedAboveThreshold END))) WHERE `morale` < 100;';
                break;

            case 'mysql':
                $sql = 'UPDATE `dominions` SET `morale` = LEAST(100, (`morale` + IF(`morale` < :moraleThreshold, :moraleAddedBelowThreshold, :moraleAddedAboveThreshold))) WHERE `morale` < 100;';
                break;
        }

        $affected = DB::update($sql, $bindings);

        Log::debug("Ticked morale, {$affected} dominion(s) affected");
    }

    public function tickDominionSpyStrength()
    {
        Log::debug('Tick spy strength started');

        $sql = null;
        $bindings = [
            'spyStrengthAdded' => 4, // todo: get values from EspionageCalculator for Spy Master and Dark Artistry techs
        ];

        switch ($this->databaseDriver) {
            case 'sqlite':
                $sql = 'UPDATE `dominions` SET `spy_strength` = MIN(100, `spy_strength` + :spyStrengthAdded) WHERE `spy_strength` < 100;';
                break;

            case 'mysql':
                $sql = 'UPDATE `dominions` SET `spy_strength` = LEAST(100, `spy_strength` + :spyStrengthAdded) WHERE `spy_strength` < 100;';
                break;
        }

        $affected = DB::update($sql, $bindings);

        Log::debug("Ticked spy strength, {$affected} dominion(s) affected");
    }

    public function tickDominionWizardStrength()
    {
        Log::debug('Tick wizard strength started');

        $sql = null;
        $bindings = [
            'wizardStrengthAdded' => 5, // todo: get values from SpellCalculator for Master of Magi and Dark Artistry techs
        ];

        switch ($this->databaseDriver) {
            case 'sqlite':
                $sql = 'UPDATE `dominions` SET `wizard_strength` = MIN(100, `wizard_strength` + :wizardStrengthAdded) WHERE `wizard_strength` < 100;';
                break;

            case 'mysql':
                $sql = 'UPDATE `dominions` SET `wizard_strength` = LEAST(100, `wizard_strength` + :wizardStrengthAdded) WHERE `wizard_strength` < 100;';
                break;
        }

        $affected = DB::update($sql, $bindings);

        Log::debug("Ticked wizard strength, {$affected} dominion(s) affected");
    }

    public function tickExplorationQueue()
    {
        Log::debug('Tick exploration queue');

        $rows = DB::table('queue_exploration')->where('hours', 0)->get();

        foreach ($rows as $row) {
            DB::table('dominions')->where('id', $row->dominion_id)->update([
                "land_{$row->land_type}" => DB::raw("`land_{$row->land_type}` + {$row->amount}"),
            ]);
        }

        $affectedFinished = DB::table('queue_exploration')->where('hours', 0)->delete();

        $affectedUpdated = DB::table('queue_exploration')->update([
            'hours' => DB::raw('`hours` - 1'),
            'updated_at' => new Carbon(),
        ]);

        Log::debug("Ticked exploration queue, {$affectedUpdated} updated, {$affectedFinished} finished");
    }

    public function tickConstructionQueue()
    {
        Log::debug('Tick construction queue');

        $rows = DB::table('queue_construction')->where('hours', 0)->get();

        foreach ($rows as $row) {
            DB::table('dominions')->where('id', $row->dominion_id)->update([
                "building_{$row->building}" => DB::raw("`building_{$row->building}` + {$row->amount}"),
            ]);
        }

        $affectedFinished = DB::table('queue_construction')->where('hours', 0)->delete();

        $affectedUpdated = DB::table('queue_construction')->update([
            'hours' => DB::raw('`hours` - 1'),
            'updated_at' => new Carbon(),
        ]);

        Log::debug("Ticked construction queue, {$affectedUpdated} updated, {$affectedFinished} finished");
    }

    public function tickDominionNetworth()
    {
        Log::debug('Tick dominion networth');

        $dominions = $this->dominions->all();

        foreach ($dominions as $dominion) {
            /** @var $dominion Dominion */
            $this->initCalculatorsForDominion($dominion);

            $dominion->networth = $this->networthCalculator->getDominionNetworth($dominion);
            $dominion->save();
        }
    }

    protected function initCalculatorDependencies()
    {
        foreach (app()->tagged('initializableCalculators') as $calculator) {
            $calculator->initDependencies();
        }
    }

    protected function initCalculatorsForDominion(Dominion $dominion)
    {
        foreach (app()->tagged('dominionCalculators') as $calculator) {
            $calculator->init($dominion);
        }
    }
}
