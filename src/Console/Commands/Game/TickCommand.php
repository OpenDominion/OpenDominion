<?php

namespace OpenDominion\Console\Commands\Game;

use Carbon\Carbon;
use Config;
use DB;
use Illuminate\Console\Command;
use Log;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use RuntimeException;

// todo: refactor this class

class TickCommand extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:tick';

    /** @var string The console command description. */
    protected $description = 'Ticks the game';

    /** @var string */
    protected $databaseDriver;

    /** @var int[] */
    protected $dominionsIdsToUpdate = [];

    /** @var CasualtiesCalculator */
    protected $casualtiesCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var ProductionCalculator */
    protected $productionCalculator;

    /** @var Carbon */
    protected $now;

    /**
     * GameTickCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);

        $this->now = Carbon::now();
    }

    /**
     * Execute the console command.
     *
     * @throws RuntimeException
     */
    public function handle(): void
    {
        $this->setDatabaseDriver();

        Log::debug('Tick started');

        // todo: DB::transaction(function () { ... });
        DB::beginTransaction();

        $this->fetchDominionsToUpdate();

        $this->tickExplorationQueue();
        $this->tickConstructionQueue();
        $this->tickTrainingQueue();
        // todo: Military returning queue

        $this->tickDominionResources();
        // todo: Population (peasants & draftees)
        $this->tickDominionMorale();
        $this->tickDominionSpyStrength();
        $this->tickDominionWizardStrength();

        $this->tickActiveSpells();

        $this->resetDailyBonuses();
        $this->updateDailyRankings();

        DB::commit();

        Log::info('Tick');
    }

    /**
     * Saves the database driver name so we can easily reference it later when we need driver-specific SQL.
     *
     * Only MySQL and Sqlite are supported at this time.
     *
     * @throws RuntimeException
     */
    public function setDatabaseDriver()
    {
        $connector = Config::get('database.default');
        $driver = Config::get("database.connections.{$connector}.driver");

        if (!in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException("Database driver {$driver} not supported for game:tick command :(");
        }

        $this->databaseDriver = $driver;
    }

    public function fetchDominionsToUpdate()
    {
        $this->dominionsIdsToUpdate = DB::table('dominions')
            ->select('dominions.id')
            ->join('rounds', function ($join) {
                $join->on('rounds.id', '=', 'dominions.round_id');
            })
            ->where('rounds.start_date', '<=', Carbon::now())
            ->where('rounds.end_date', '>', Carbon::now())
            ->get()
            ->map(function ($dominion) {
                return $dominion->id;
            })
            ->toArray();
    }

    /**
     * Ticks dominion resources.
     */
    public function tickDominionResources()
    {
        Log::debug('Tick resources started');

        $dominions = $this->getDominionsToUpdate();

        foreach ($dominions as $dominion) {

            // Resources
            $dominion->resource_platinum += $this->productionCalculator->getPlatinumProduction($dominion);
            $dominion->resource_food += $this->productionCalculator->getFoodNetChange($dominion);
            $dominion->resource_lumber += $this->productionCalculator->getLumberNetChange($dominion);
            $dominion->resource_mana += $this->productionCalculator->getManaNetChange($dominion);
            $dominion->resource_ore += $this->productionCalculator->getOreProduction($dominion);
            $dominion->resource_gems += $this->productionCalculator->getGemProduction($dominion);
            $dominion->resource_boats += $this->productionCalculator->getBoatProduction($dominion);

            // Casualties by starvation
            // todo: this probably needs to go in a CasualtyService class or something
            if ($dominion->resource_food < 0) {
                $casualties = $this->casualtiesCalculator->getStarvationCasualtiesByUnitType($dominion);

                foreach($casualties as $unit => $unitCasualties) {
                    $dominion->{$unit} -= $unitCasualties;
                }

                $dominion->resource_food = 0;
            }

            // Population
            $populationPeasantGrowth = $this->populationCalculator->getPopulationPeasantGrowth($dominion);

            $dominion->peasants += $populationPeasantGrowth;
            $dominion->peasants_last_hour = $populationPeasantGrowth;
            $dominion->military_draftees += $this->populationCalculator->getPopulationDrafteeGrowth($dominion);

            $dominion->save();
        }

        $affected = $dominions->count();

        Log::debug("Ticked resources, {$affected} dominion(s) affected");
    }

    /**
     * Ticks dominion morale.
     */
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
                $sql = ('UPDATE `dominions` SET `morale` = MIN(100, (`morale` + (CASE WHEN (`morale` < :moraleThreshold) THEN :moraleAddedBelowThreshold ELSE :moraleAddedAboveThreshold END))) WHERE `morale` < 100 AND `id` IN (' . implode(', ', $this->dominionsIdsToUpdate) . ');');
                break;

            case 'mysql':
                $sql = ('UPDATE `dominions` SET `morale` = LEAST(100, (`morale` + IF(`morale` < :moraleThreshold, :moraleAddedBelowThreshold, :moraleAddedAboveThreshold))) WHERE `morale` < 100 AND `id` IN (' . implode(', ', $this->dominionsIdsToUpdate) . ');');
                break;
        }

        $affected = DB::update($sql, $bindings);

        Log::debug("Ticked morale, {$affected} dominion(s) affected");
    }

    /**
     * Ticks dominion spy strength.
     */
    public function tickDominionSpyStrength()
    {
        Log::debug('Tick spy strength started');

        $sql = null;
        $bindings = [
            'spyStrengthAdded' => 4, // todo: get values from EspionageCalculator for Spy Master and Dark Artistry techs
        ];

        switch ($this->databaseDriver) {
            case 'sqlite':
                $sql = ('UPDATE `dominions` SET `spy_strength` = MIN(100, `spy_strength` + :spyStrengthAdded) WHERE `spy_strength` < 100 AND `id` IN (' . implode(', ', $this->dominionsIdsToUpdate) . ');');
                break;

            case 'mysql':
                $sql = ('UPDATE `dominions` SET `spy_strength` = LEAST(100, `spy_strength` + :spyStrengthAdded) WHERE `spy_strength` < 100 AND `id` IN (' . implode(', ', $this->dominionsIdsToUpdate) . ');');
                break;
        }

        $affected = DB::update($sql, $bindings);

        Log::debug("Ticked spy strength, {$affected} dominion(s) affected");
    }

    /**
     * Ticks dominion wizard strength.
     */
    public function tickDominionWizardStrength()
    {
        Log::debug('Tick wizard strength started');

        $sql = null;
        $bindings = [
            'wizardStrengthAdded' => 4, // todo: get values from SpellCalculator for Master of Magi and Dark Artistry techs
        ];

        switch ($this->databaseDriver) {
            case 'sqlite':
                $sql = ('UPDATE `dominions` SET `wizard_strength` = MIN(100, `wizard_strength` + :wizardStrengthAdded) WHERE `wizard_strength` < 100 AND `id` IN (' . implode(', ', $this->dominionsIdsToUpdate) . ');');
                break;

            case 'mysql':
                $sql = ('UPDATE `dominions` SET `wizard_strength` = LEAST(100, `wizard_strength` + :wizardStrengthAdded) WHERE `wizard_strength` < 100 AND `id` IN (' . implode(', ', $this->dominionsIdsToUpdate) . ');');
                break;
        }

        $affected = DB::update($sql, $bindings);

        Log::debug("Ticked wizard strength, {$affected} dominion(s) affected");
    }

    /**
     * Ticks dominion exploration queue.
     */
    public function tickExplorationQueue()
    {
        Log::debug('Tick exploration queue');

        // Two-step process to avoid getting UNIQUE constraint integrity errors since we can't reliably use deferred
        // transactions, deferred update queries or update+orderby cross-database
        DB::table('queue_exploration')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('hours', '>', 0)
            ->update([
                'hours' => DB::raw('-(`hours` - 1)'),
            ]);

        $affectedUpdated = DB::table('queue_exploration')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('hours', '<', 0)
            ->update([
                'hours' => DB::raw('-`hours`'),
                'updated_at' => new Carbon(),
            ]);

        $rows = DB::table('queue_exploration')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('hours', 0)
            ->get();

        foreach ($rows as $row) {
            DB::table('dominions')->where('id', $row->dominion_id)->update([
                "land_{$row->land_type}" => DB::raw("`land_{$row->land_type}` + {$row->amount}"),
            ]);
        }

        $affectedFinished = DB::table('queue_exploration')
            ->where('hours', 0)
            ->delete();

        $affectedUpdated -= $affectedFinished;

        Log::debug("Ticked exploration queue, {$affectedUpdated} updated, {$affectedFinished} finished");
    }

    /**
     * Ticks dominion construction queue.
     */
    public function tickConstructionQueue()
    {
        Log::debug('Tick construction queue');

        // Two-step process to avoid getting UNIQUE constraint integrity errors since we can't reliably use deferred
        // transactions, deferred update queries or update+orderby cross-database
        DB::table('queue_construction')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('hours', '>', 0)
            ->update([
                'hours' => DB::raw('-(`hours` - 1)'),
            ]);

        $affectedUpdated = DB::table('queue_construction')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('hours', '<', 0)
            ->update([
                'hours' => DB::raw('-`hours`'),
                'updated_at' => new Carbon(),
            ]);

        $rows = DB::table('queue_construction')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('hours', 0)
            ->get();

        foreach ($rows as $row) {
            DB::table('dominions')->where('id', $row->dominion_id)->update([
                "building_{$row->building}" => DB::raw("`building_{$row->building}` + {$row->amount}"),
            ]);
        }

        $affectedFinished = DB::table('queue_construction')->where('hours', 0)->delete();

        $affectedUpdated -= $affectedFinished;

        Log::debug("Ticked construction queue, {$affectedUpdated} updated, {$affectedFinished} finished");
    }

    /**
     * Ticks dominion training queue.
     */
    public function tickTrainingQueue()
    {
        Log::debug('Tick training queue');

        // Two-step process to avoid getting UNIQUE constraint integrity errors since we can't reliably use deferred
        // transactions, deferred update queries or update+orderby cross-database
        DB::table('queue_training')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('hours', '>', 0)
            ->update([
                'hours' => DB::raw('-(`hours` - 1)'),
            ]);

        $affectedUpdated = DB::table('queue_training')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('hours', '<', 0)
            ->update([
                'hours' => DB::raw('-`hours`'),
                'updated_at' => new Carbon(),
            ]);

        $rows = DB::table('queue_training')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('hours', 0)
            ->get();

        foreach ($rows as $row) {
            DB::table('dominions')->where('id', $row->dominion_id)->update([
                "military_{$row->unit_type}" => DB::raw("`military_{$row->unit_type}` + {$row->amount}"),
            ]);
        }

        $affectedFinished = DB::table('queue_training')->where('hours', 0)->delete();

        $affectedUpdated -= $affectedFinished;

        Log::debug("Ticked training queue, {$affectedUpdated} updated, {$affectedFinished} finished");
    }

    public function tickActiveSpells()
    {
        Log::debug('Ticking active spells');

        // Two-step process to avoid getting UNIQUE constraint integrity errors since we can't reliably use deferred
        // transactions, deferred update queries or update+orderby cross-database
        DB::table('active_spells')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('duration', '>', 0)
            ->update([
                'duration' => DB::raw('-(`duration` - 1)'),
            ]);

        $affectedUpdated = DB::table('active_spells')
            ->whereIn('dominion_id', $this->dominionsIdsToUpdate)
            ->where('duration', '<', 0)
            ->update([
                'duration' => DB::raw('-`duration`'),
                'updated_at' => new Carbon(),
            ]);

        $affectedFinished = DB::table('active_spells')->where('duration', 0)->delete();

        Log::debug("Ticked active spells, {$affectedUpdated} updated, {$affectedFinished} finished");
    }

    /**
     * Resets daily bonuses.
     */
    public function resetDailyBonuses()
    {
        if (Carbon::now()->hour !== 0) {
            return;
        }

        Log::debug('Resetting daily bonuses');

        foreach ($this->getDominionsToUpdate() as $dominion) {
            $dominion->daily_platinum = false;
            $dominion->daily_land = false;
            $dominion->save();
        }
    }

    public function updateDailyRankings()
    {
        if (Carbon::now()->hour !== 0) {
            return;
        }

        Log::debug('Updating daily rankings');

        // todo: needs updating per round. make GameTickService and tick each round individually. at least for rankings

        // First pass: Saving land and networth
        Dominion::with(['race', 'realm'])->whereIn('id', $this->dominionsIdsToUpdate)->chunk(50, function ($dominions) {
            foreach ($dominions as $dominion) {
                $where = [
                    'round_id' => (int)$dominion->round_id,
                    'dominion_id' => $dominion->id,
                ];

                $data = [
                    'dominion_name' => $dominion->name,
                    'race_name' => $dominion->race->name,
                    'realm_number' => $dominion->realm->number,
                    'realm_name' => $dominion->realm->name,
                    'land' => $this->landCalculator->getTotalLand($dominion),
                    'networth' => $this->networthCalculator->getDominionNetworth($dominion),
                ];

                $result = DB::table('daily_rankings')->where($where)->get();

                if ($result->isEmpty()) {
                    $row = $where + $data + [
                        'created_at' => $dominion->created_at,
                        'updated_at' => $this->now,
                    ];

                    DB::table('daily_rankings')->insert($row);
                } else {
                    $row = $data + [
                        'updated_at' => $this->now,
                    ];

                    DB::table('daily_rankings')->where($where)->update($row);
                }
            }
        });

        // Second pass: Calculating ranks
        $result = DB::table('daily_rankings')
            ->orderBy('land', 'desc')
            ->orderBy('land_rank')
            ->orderBy('created_at')
            ->get();

        $rank = 1;

        foreach ($result as $row) {
            DB::table('daily_rankings')
                ->where('id', $row->id)
                ->update([
                    'land_rank' => $rank,
                    'land_rank_change' => (($row->land_rank !== null) ? ($row->land_rank - $rank) : 0),
                ]);

            $rank++;
        }

        $result = DB::table('daily_rankings')
            ->orderBy('networth', 'desc')
            ->orderBy('networth_rank')
            ->orderBy('created_at')
            ->get();

        $rank = 1;

        foreach ($result as $row) {
            DB::table('daily_rankings')
                ->where('id', $row->id)
                ->update([
                    'networth_rank' => $rank,
                    'networth_rank_change' => (($row->networth_rank !== null) ? ($row->networth_rank - $rank) : 0),
                ]);

            $rank++;
        }
    }

    protected function getDominionsToUpdate()
    {
        return Dominion::whereIn('id', $this->dominionsIdsToUpdate)->get();
    }
}
