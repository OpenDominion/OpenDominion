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
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setDatabaseDriver();

        Log::debug('Tick started');

        DB::beginTransaction();

        // todo: below only for current active rounds

        $this->tickDominionResources();
        // todo: Population (peasants & draftees)
        $this->tickDominionMorale();
        $this->tickExplorationQueue();
        $this->tickConstructionQueue();
        // todo: Military training queue
        // todo: Military returning queue
        // todo: Magic queue

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
            foreach (app()->tagged('calculators') as $calculator) {
                $calculator->init($dominion);
            }

            // Resources
            $dominion->resource_platinum += $this->productionCalculator->getPlatinumProduction();
            $dominion->resource_food += $this->productionCalculator->getFoodNetChange();
            // todo: if food < 0 then food = 0?
            $dominion->resource_lumber += $this->productionCalculator->getLumberNetChange();
            // todo: if lumber < 0 then lumber = 0?
            // todo: mana
            // todo: if mana < 0 then mana = 0?
            // todo: ore
            // todo: gems
            // todo: boats

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
}
