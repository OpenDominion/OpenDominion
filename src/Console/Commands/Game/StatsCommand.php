<?php

namespace OpenDominion\Console\Commands\Game;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Round;

class StatsCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:stats';

    /** @var string The console command description. */
    protected $description = 'Generates game stats';

    /**
     * StatsCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $activeRounds = Round::activeRankings()->get();

        foreach ($activeRounds as $round) {
            $daysInRound = $round->daysInRound() - 1;

            // Get active dominions
            $dominions = Dominion::with('race')
                ->where([
                    'round_id' => $round->id,
                    'locked_at' => null,
                    'protection_ticks_remaining' => 0
                ])
                ->get();

            // Get dominion data
            $totalDominions = $dominions->count();
            $totalPlayerDominions = $dominions->where('user_id', null)->count();
            $totalNonPlayerDominions = $dominions->where('user_id', '!=', null)->count();
            $raceSelection = $dominions->where('user_id', '!=', null)
                ->groupBy('race.name')
                ->map(function ($item, $key) {
                    return $item->count();
                })
                ->sortKeys();
            $averageLandByRace = $dominions->where('user_id', '!=', null)
                ->map(function ($item, $key) {
                    $item['land'] = $item->land_plain + $item->land_hill + $item->land_cavern + $item->land_forest + $item->land_water + $item->land_swamp + $item->land_mountain;
                    return $item;
                })
                ->groupBy('race.name')
                ->map(function ($item, $key) {
                    return (int) round($item->avg('land'));
                })
                ->sortKeys();
            $averageLandOutput = print_r($averageLandByRace->all(), true);

            // Get daily game events
            $events = GameEvent::with(['source', 'target'])
                ->where([
                    'round_id' => $round->id,
                    'type' => 'invasion'
                ])
                ->where('created_at', '>', now()->subDays(1))
                ->where('created_at', '<', now())
                ->get()
                ->map(function ($item, $key) {
                    $item['landGain'] = 0;
                    $item['landRatio'] = 0;
                    if (isset($item->data['attacker']['landConquered'])) {
                        $item['landGain'] = array_sum($item->data['attacker']['landConquered']);
                        $item['landRatio'] = $item->data['defender']['landSize'] / $item->data['attacker']['landSize'];
                    }
                    return $item;
                });

            // Get game event data
            $totalInvasions = $events->count();
            $invasionsSuccessful = $events->where('data.result.success', true)->count();
            $invasionsSuccessfulPercent = round($totalInvasions ? ($invasionsSuccessful / $totalInvasions * 100) : 0, 2);
            $invasionsFailed = $events->where('data.result.success', false)->count();
            $invasionsFailedPercent = round($totalInvasions ? ($invasionsFailed / $totalInvasions * 100) : 0, 2);
            $invasionsBots = $events->where('target.user_id', null)->count();
            $invasionsBotsPercent = round($totalInvasions ? ($invasionsBots / $totalInvasions * 100) : 0, 2);
            $invasionsPlayers = $events->where('target.user_id', '!=', null)->count();
            $invasionsPlayersPercent = round($totalInvasions ? ($invasionsPlayers / $totalInvasions * 100) : 0, 2);
            $uniqueAttackers = $events->unique('source.id')->count();
            $attackersPercent = round($totalDominions ? ($uniqueAttackers / $totalDominions * 100) : 0, 2);
            $uniqueDefenders = $events->unique('target.id')->count();
            $averageAttackCount = $events->groupBy('source.id')
                ->map(function ($item, $key) {
                    return $item->count();
                })
                ->avg();
            $averageAttackCount = round($averageAttackCount, 2);
            $maxAttackCount = $events->groupBy('source.id')
                ->map(function ($item, $key) {
                    return $item->count();
                })
                ->max();
            $averageLandGain = $events->avg('landGain');
            $maxLandGain = $events->max('landGain');
            if ($maxLandGain) {
                $maxLandAttacker = $events->where('landGain', $maxLandGain)->first()->source->name;
                $maxLandDefender = $events->where('landGain', $maxLandGain)->first()->target->name;
                $largestHit = "{$maxLandAttacker} invaded {$maxLandDefender} for {$maxLandGain} acres";
            } else {
                $largestHit = '';
            }

            $output = "
**Day {$daysInRound} Statistics**

Active Dominions: {$totalDominions}
Players: {$totalPlayerDominions}
Bots: {$totalNonPlayerDominions}

Total Invasions: {$totalInvasions}
Successful Invasions: {$invasionsSuccessful} ({$invasionsSuccessfulPercent}%)
Failed Invasions: {$invasionsFailed} ({$invasionsFailedPercent}%)
Invasions against bots: {$invasionsBots} ({$invasionsBotsPercent}%)
Invasions against players: {$invasionsPlayers} ({$invasionsPlayersPercent}%)

Unique attackers: {$uniqueAttackers} ({$attackersPercent}% of players attacked)
Unique targets: {$uniqueDefenders}

Average number of attacks by attacking players: {$averageAttackCount}
Highest number of attacks by a single player: {$maxAttackCount}
Average land gain: {$averageLandGain} acres
Largest hit: {$largestHit}
            ";

            $webhook = config('app.discord_stats_webhook');
            if ($webhook) {
                $client = new Client();
                $response = $client->post($webhook, ['form_params' => [
                    'content' => $output
                ]]);
                if ($response->getStatusCode() != 204) {
                    $this->info('Failed to POST stats to webhook.');
                }
                $response = $client->post($webhook, ['form_params' => [
                    'content' => "Average Land By Race\n{$averageLandOutput}"
                ]]);
                if ($response->getStatusCode() != 204) {
                    $this->info('Failed to POST charts to webhook.');
                }
            } else {
                $this->info($output);
            }
        }
    }
}
