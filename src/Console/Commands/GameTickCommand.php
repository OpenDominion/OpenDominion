<?php

namespace OpenDominion\Console\Commands;

use Carbon\Carbon;
use Config;
use DB;
use Exception;
use Illuminate\Console\Command;
use Log;

class GameTickCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:tick';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ticks the game';

    /** @var string */
    protected $databaseDriver;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
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

        // todo: Resources
        // todo: Population (peasants & draftees)
        $this->tickMorale();
        $this->tickExplorationQueue();
        // todo: Construction queue
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

    public function tickMorale()
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
}
