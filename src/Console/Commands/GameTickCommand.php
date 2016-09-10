<?php

namespace OpenDominion\Console\Commands;

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
        // todo: Construction queue
        // todo: Exploration queue
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
}
