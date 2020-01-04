<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Log;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Dominion\Tick;
use OpenDominion\Models\Round;
use OpenDominion\Services\NotificationService;
use Throwable;

class TickService
{
    /** @var Carbon */
    protected $now;

    /** @var CasualtiesCalculator */
    protected $casualtiesCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var ProductionCalculator */
    protected $productionCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * TickService constructor.
     */
    public function __construct()
    {
        $this->now = now();
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->spellCalculator = app(SpellCalculator::class);

        /* These calculators need to ignore queued resources for the following tick */
        $this->populationCalculator->setForTick(true);
        $this->queueService->setForTick(true);
    }

    /**
     * Does an hourly tick on all active dominions.
     *
     * @throws Exception|Throwable
     */
    public function tickHourly()
    {
        Log::debug('Hourly tick started');

        // Hourly tick

        //DB::connection()->enableQueryLog();

        $activeRounds = Round::active()->get();

        foreach ($activeRounds as $round) {
            // Precalculate all dominion ticks on hour 0
            if ($this->now->diffInHours($round->start_date) === 0) {
                $dominions = $round->dominions()
                    ->with([
                        'race',
                        'race.perks',
                        'race.units',
                        'race.units.perks',
                    ])
                    ->get();

                foreach ($dominions as $dominion) {
                    $this->precalculateTick($dominion, true);
                }

                continue;
            }

            DB::transaction(function () use ($round) {
                // Update dominions
                DB::table('dominions')
                    ->join('dominion_tick', 'dominions.id', '=', 'dominion_tick.dominion_id')
                    ->where('dominions.round_id', $round->id)
                    ->update([
                        'dominions.prestige' => DB::raw('dominions.prestige + dominion_tick.prestige'),
                        'dominions.peasants' => DB::raw('dominions.peasants + dominion_tick.peasants'),
                        'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                        'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                        'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                        'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),
                        'dominions.resource_platinum' => DB::raw('dominions.resource_platinum + dominion_tick.resource_platinum'),
                        'dominions.resource_food' => DB::raw('dominions.resource_food + dominion_tick.resource_food'),
                        'dominions.resource_lumber' => DB::raw('dominions.resource_lumber + dominion_tick.resource_lumber'),
                        'dominions.resource_mana' => DB::raw('dominions.resource_mana + dominion_tick.resource_mana'),
                        'dominions.resource_ore' => DB::raw('dominions.resource_ore + dominion_tick.resource_ore'),
                        'dominions.resource_gems' => DB::raw('dominions.resource_gems + dominion_tick.resource_gems'),
                        'dominions.resource_tech' => DB::raw('dominions.resource_tech + dominion_tick.resource_tech'),
                        'dominions.resource_boats' => DB::raw('dominions.resource_boats + dominion_tick.resource_boats'),
                        'dominions.military_draftees' => DB::raw('dominions.military_draftees + dominion_tick.military_draftees'),
                        'dominions.military_unit1' => DB::raw('dominions.military_unit1 + dominion_tick.military_unit1'),
                        'dominions.military_unit2' => DB::raw('dominions.military_unit2 + dominion_tick.military_unit2'),
                        'dominions.military_unit3' => DB::raw('dominions.military_unit3 + dominion_tick.military_unit3'),
                        'dominions.military_unit4' => DB::raw('dominions.military_unit4 + dominion_tick.military_unit4'),
                        'dominions.military_spies' => DB::raw('dominions.military_spies + dominion_tick.military_spies'),
                        'dominions.military_wizards' => DB::raw('dominions.military_wizards + dominion_tick.military_wizards'),
                        'dominions.military_archmages' => DB::raw('dominions.military_archmages + dominion_tick.military_archmages'),
                        'dominions.land_plain' => DB::raw('dominions.land_plain + dominion_tick.land_plain'),
                        'dominions.land_mountain' => DB::raw('dominions.land_mountain + dominion_tick.land_mountain'),
                        'dominions.land_swamp' => DB::raw('dominions.land_swamp + dominion_tick.land_swamp'),
                        'dominions.land_cavern' => DB::raw('dominions.land_cavern + dominion_tick.land_cavern'),
                        'dominions.land_forest' => DB::raw('dominions.land_forest + dominion_tick.land_forest'),
                        'dominions.land_hill' => DB::raw('dominions.land_hill + dominion_tick.land_hill'),
                        'dominions.land_water' => DB::raw('dominions.land_water + dominion_tick.land_water'),
                        'dominions.discounted_land' => DB::raw('dominions.discounted_land + dominion_tick.discounted_land'),
                        'dominions.building_home' => DB::raw('dominions.building_home + dominion_tick.building_home'),
                        'dominions.building_alchemy' => DB::raw('dominions.building_alchemy + dominion_tick.building_alchemy'),
                        'dominions.building_farm' => DB::raw('dominions.building_farm + dominion_tick.building_farm'),
                        'dominions.building_smithy' => DB::raw('dominions.building_smithy + dominion_tick.building_smithy'),
                        'dominions.building_masonry' => DB::raw('dominions.building_masonry + dominion_tick.building_masonry'),
                        'dominions.building_ore_mine' => DB::raw('dominions.building_ore_mine + dominion_tick.building_ore_mine'),
                        'dominions.building_gryphon_nest' => DB::raw('dominions.building_gryphon_nest + dominion_tick.building_gryphon_nest'),
                        'dominions.building_tower' => DB::raw('dominions.building_tower + dominion_tick.building_tower'),
                        'dominions.building_wizard_guild' => DB::raw('dominions.building_wizard_guild + dominion_tick.building_wizard_guild'),
                        'dominions.building_temple' => DB::raw('dominions.building_temple + dominion_tick.building_temple'),
                        'dominions.building_diamond_mine' => DB::raw('dominions.building_diamond_mine + dominion_tick.building_diamond_mine'),
                        'dominions.building_school' => DB::raw('dominions.building_school + dominion_tick.building_school'),
                        'dominions.building_lumberyard' => DB::raw('dominions.building_lumberyard + dominion_tick.building_lumberyard'),
                        'dominions.building_forest_haven' => DB::raw('dominions.building_forest_haven + dominion_tick.building_forest_haven'),
                        'dominions.building_factory' => DB::raw('dominions.building_factory + dominion_tick.building_factory'),
                        'dominions.building_guard_tower' => DB::raw('dominions.building_guard_tower + dominion_tick.building_guard_tower'),
                        'dominions.building_shrine' => DB::raw('dominions.building_shrine + dominion_tick.building_shrine'),
                        'dominions.building_barracks' => DB::raw('dominions.building_barracks + dominion_tick.building_barracks'),
                        'dominions.building_dock' => DB::raw('dominions.building_dock + dominion_tick.building_dock'),
                        'dominions.stat_total_platinum_production' => DB::raw('dominions.stat_total_platinum_production + dominion_tick.resource_platinum'),
                        'dominions.stat_total_food_production' => DB::raw('dominions.stat_total_food_production + dominion_tick.resource_food_production'),
                        'dominions.stat_total_lumber_production' => DB::raw('dominions.stat_total_lumber_production + dominion_tick.resource_lumber_production'),
                        'dominions.stat_total_mana_production' => DB::raw('dominions.stat_total_mana_production + dominion_tick.resource_mana_production'),
                        'dominions.stat_total_ore_production' => DB::raw('dominions.stat_total_ore_production + dominion_tick.resource_ore'),
                        'dominions.stat_total_gem_production' => DB::raw('dominions.stat_total_gem_production + dominion_tick.resource_gems'),
                        'dominions.stat_total_tech_production' => DB::raw('dominions.stat_total_tech_production + dominion_tick.resource_tech'),
                        'dominions.stat_total_boat_production' => DB::raw('dominions.stat_total_boat_production + dominion_tick.resource_boats'),
                        'dominions.highest_land_achieved' => DB::raw('dominions.highest_land_achieved + dominion_tick.highest_land_achieved'),
                        'dominions.last_tick_at' => DB::raw('now()')
                    ]);

                // Update spells
                DB::table('active_spells')
                    ->join('dominions', 'active_spells.dominion_id', '=', 'dominions.id')
                    ->where('dominions.round_id', $round->id)
                    ->where('duration', '>', 0)
                    ->update([
                        'duration' => DB::raw('duration - 1'),
                        'active_spells.updated_at' => $this->now,
                    ]);

                // Update queues
                // Two-step process to avoid getting UNIQUE constraint integrity errors
                // since we can't reliably use deferred transactions, deferred update
                // queries or update+orderby cross-database vendors
                DB::table('dominion_queue')
                    ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
                    ->where('dominions.round_id', $round->id)
                    ->where('hours', '>', 0)
                    ->update([
                        'hours' => DB::raw('-(`hours` - 1)'),
                    ]);

                DB::table('dominion_queue')
                    ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
                    ->where('dominions.round_id', $round->id)
                    ->where('hours', '<', 0)
                    ->update([
                        'hours' => DB::raw('-`hours`'),
                        'dominion_queue.updated_at' => $this->now,
                    ]);
            }, 5);

            Log::info(sprintf(
                'Ticked %s dominions in %s ms in %s',
                number_format($round->dominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));

            $this->now = now();
        }

        foreach ($activeRounds as $round) {
            $dominions = $round->dominions()
                ->with([
                    'race',
                    'race.perks',
                    'race.units',
                    'race.units.perks',
                ])
                ->get();

            foreach ($dominions as $dominion) {
                DB::transaction(function () use ($dominion) {
                    if (!empty($dominion->tick->starvation_casualties)) {
                        $this->notificationService->queueNotification(
                            'starvation_occurred',
                            $dominion->tick->starvation_casualties
                        );
                    }

                    $this->cleanupActiveSpells($dominion);
                    $this->cleanupQueues($dominion);

                    $this->notificationService->sendNotifications($dominion, 'hourly_dominion');

                    $this->precalculateTick($dominion, true);
                }, 5);
            }

            Log::info(sprintf(
                'Cleaned up queues, sent notifications, and precalculated %s dominions in %s ms in %s',
                number_format($round->dominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));

            $this->now = now();
        }

        // Update rankings
        if (($this->now->hour % 6) === 0) {
            foreach ($activeRounds as $round) {
                $this->updateDailyRankings($round->dominions);

                Log::info(sprintf(
                    'Updated rankings in %s ms in %s',
                    number_format($this->now->diffInMilliseconds(now())),
                    $round->name
                ));

                $this->now = now();
            }
        }
    }

    /**
     * Does a daily tick on all active dominions and rounds.
     *
     * @throws Exception|Throwable
     */
    public function tickDaily()
    {
        Log::debug('Daily tick started');

        DB::transaction(function () {
            foreach (Round::with('dominions')->active()->get() as $round) {
                // Ignore the first hour 0 of the round
                if ($this->now->diffInHours($round->start_date) === 0) {
                    continue;
                }

                // toBase required to prevent ambiguous updated_at column in query
                $round->dominions()->toBase()->update([
                    'daily_platinum' => false,
                    'daily_land' => false,
                ], [
                    'event' => 'tick',
                ]);
            }
        });

        Log::info('Daily tick finished');
    }

    protected function cleanupActiveSpells(Dominion $dominion)
    {
        $finished = DB::table('active_spells')
            ->where('dominion_id', $dominion->id)
            ->where('duration', '<=', 0)
            ->get();

        $beneficialSpells = [];
        $harmfulSpells = [];

        foreach ($finished as $row) {
            if ($row->cast_by_dominion_id == $dominion->id) {
                $beneficialSpells[] = $row->spell;
            } else {
                $harmfulSpells[] = $row->spell;
            }
        }

        if (!empty($beneficialSpells)) {
            $this->notificationService->queueNotification('beneficial_magic_dissipated', $beneficialSpells);
        }

        if (!empty($harmfulSpells)) {
            $this->notificationService->queueNotification('harmful_magic_dissipated', $harmfulSpells);
        }

        DB::table('active_spells')
            ->where('dominion_id', $dominion->id)
            ->where('duration', '<=', 0)
            ->delete();
    }

    protected function cleanupQueues(Dominion $dominion)
    {
        $finished = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '<=', 0)
            ->get();

        foreach ($finished->groupBy('source') as $source => $group) {
            $resources = [];
            foreach ($group as $row) {
                $resources[$row->resource] = $row->amount;
            }

            if ($source === 'invasion') {
                $notificationType = 'returning_completed';
            } else {
                $notificationType = "{$source}_completed";
            }

            $this->notificationService->queueNotification($notificationType, $resources);
        }

        // Cleanup
        DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '<=', 0)
            ->delete();
    }

    public function precalculateTick(Dominion $dominion, ?bool $saveHistory = false): void
    {
        /** @var Tick $tick */
        $tick = Tick::firstOrCreate(
            ['dominion_id' => $dominion->id]
        );

        if ($saveHistory) {
            // Save a dominion history record
            $dominionHistoryService = app(HistoryService::class);

            $changes = array_filter($tick->getAttributes(), static function ($value, $key) {
                return (
                    !in_array($key, [
                        'id',
                        'dominion_id',
                        'created_at',
                    ], true) &&
                    ($value != 0) // todo: strict type checking?
                );
            }, ARRAY_FILTER_USE_BOTH);

            $dominionHistoryService->record($dominion, $changes, HistoryService::EVENT_TICK);
        }

        // Reset tick values
        foreach ($tick->getAttributes() as $attr => $value) {
            if (!in_array($attr, ['id', 'dominion_id', 'updated_at', 'starvation_casualties'], true)) {
                $tick->{$attr} = 0;
            } elseif ($attr === 'starvation_casualties') {
                $tick->{$attr} = [];
            }
        }

        // Hacky refresh for dominion
        $dominion->refresh();
        $this->spellCalculator->getActiveSpells($dominion, true);

        // Queues
        $incomingQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '=', 1)
            ->get();

        foreach ($incomingQueue as $row) {
            $tick->{$row->resource} += $row->amount;
            // Temporarily add next hour's resources for accurate calculations
            $dominion->{$row->resource} += $row->amount;
        }

        $totalLand = $this->landCalculator->getTotalLand($dominion);

        // Population
        $drafteesGrowthRate = $this->populationCalculator->getPopulationDrafteeGrowth($dominion);
        $populationPeasantGrowth = $this->populationCalculator->getPopulationPeasantGrowth($dominion);

        $tick->peasants = $populationPeasantGrowth;
        $tick->military_draftees = $drafteesGrowthRate;

        // Resources
        $tick->resource_platinum += $this->productionCalculator->getPlatinumProduction($dominion);
        $tick->resource_lumber_production += $this->productionCalculator->getLumberProduction($dominion);
        $tick->resource_lumber += $this->productionCalculator->getLumberNetChange($dominion);
        $tick->resource_mana_production += $this->productionCalculator->getManaProduction($dominion);
        $tick->resource_mana += $this->productionCalculator->getManaNetChange($dominion);
        $tick->resource_ore += $this->productionCalculator->getOreProduction($dominion);
        $tick->resource_gems += $this->productionCalculator->getGemProduction($dominion);
        $tick->resource_tech += $this->productionCalculator->getTechProduction($dominion);
        $tick->resource_boats += $this->productionCalculator->getBoatProduction($dominion);
        $tick->resource_food_production += $this->productionCalculator->getFoodProduction($dominion);
        // Check for starvation before adjusting food
        $foodNetChange = $this->productionCalculator->getFoodNetChange($dominion);

        // Starvation casualties
        if (($dominion->resource_food + $foodNetChange) < 0) {
            $casualties = $this->casualtiesCalculator->getStarvationCasualtiesByUnitType(
                $dominion,
                ($dominion->resource_food + $foodNetChange)
            );

            $tick->starvation_casualties = $casualties;

            foreach ($casualties as $unitType => $unitCasualties) {
                $tick->{$unitType} -= $unitCasualties;
            }

            // Decrement to zero
            $tick->resource_food = -$dominion->resource_food;
        } else {
            // Food production
            $tick->resource_food += $foodNetChange;
        }

        // Morale
        if ($dominion->morale < 70) {
            $tick->morale = 6;
        } elseif ($dominion->morale < 100) {
            $tick->morale = min(3, 100 - $dominion->morale);
        }

        // Spy Strength
        if ($dominion->spy_strength < 100) {
            $spyStrengthAdded = 4;
            $spyStrengthAdded += $dominion->getTechPerkValue('spy_strength_recovery');

            $tick->spy_strength = min($spyStrengthAdded, 100 - $dominion->spy_strength);
        }

        // Wizard Strength
        if ($dominion->wizard_strength < 100) {
            $wizardStrengthAdded = 4;

            $wizardStrengthPerWizardGuild = 0.1;
            $wizardStrengthPerWizardGuildMax = 2;

            $wizardStrengthAdded += min(
                (($dominion->building_wizard_guild / $totalLand) * (100 * $wizardStrengthPerWizardGuild)),
                $wizardStrengthPerWizardGuildMax
            );

            $wizardStrengthAdded += $dominion->getTechPerkValue('wizard_strength_recovery');

            $tick->wizard_strength = min($wizardStrengthAdded, 100 - $dominion->wizard_strength);
        }

        // Store highest land total
        if ($totalLand > $dominion->highest_land_achieved) {
            $tick->highest_land_achieved += $totalLand - $dominion->highest_land_achieved;
        }

        foreach ($incomingQueue as $row) {
            // Reset current resources in case object is saved later
            $dominion->{$row->resource} -= $row->amount;
        }

        $tick->save();
    }

    protected function updateDailyRankings(Collection $activeDominions): void
    {
        $dominionIds = $activeDominions->pluck('id')->toArray();

        // First pass: Saving land and networth
        Dominion::with(['race', 'realm'])
            ->whereIn('id', $dominionIds)
            ->chunk(50, function ($dominions) {
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
            ->orderBy(DB::raw('COALESCE(land_rank, created_at)'))
            ->get();

        //Getting all rounds
        $rounds = DB::table('rounds')
            ->where('start_date', '<=', $this->now)
            ->where('end_date', '>', $this->now)
            ->get();

        foreach ($rounds as $round) {
            $rank = 1;

            foreach ($result as $row) {
                if ($row->round_id == (int)$round->id) {
                    DB::table('daily_rankings')
                        ->where('id', $row->id)
                        ->where('round_id', $round->id)
                        ->update([
                            'land_rank' => $rank,
                            'land_rank_change' => (($row->land_rank !== null) ? ($row->land_rank - $rank) : 0),
                        ]);

                    $rank++;
                }
            }

            $result = DB::table('daily_rankings')
                ->orderBy('networth', 'desc')
                ->orderBy(DB::raw('COALESCE(networth_rank, created_at)'))
                ->get();

            $rank = 1;

            foreach ($result as $row) {
                if ($row->round_id == (int)$round->id) {
                    DB::table('daily_rankings')
                        ->where('id', $row->id)
                        ->update([
                            'networth_rank' => $rank,
                            'networth_rank_change' => (($row->networth_rank !== null) ? ($row->networth_rank - $rank) : 0),
                        ]);

                    $rank++;
                }
            }
        }
    }
}
