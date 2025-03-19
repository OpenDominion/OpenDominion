<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Log;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Dominion\Tick;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Models\SpellPerkType;
use OpenDominion\Services\Dominion\AutomationService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Services\Dominion\HeroTournamentService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\ValorService;
use OpenDominion\Services\WonderService;
use Throwable;

class TickService
{
    /** @var Carbon */
    protected $now;

    /** @var CasualtiesCalculator */
    protected $casualtiesCalculator;

    /** @var HeroBattleService */
    protected $heroBattleService;

    /** @var HeroTournamentService */
    protected $heroTournamentService;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var OpsCalculator */
    protected $opsCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var ProductionCalculator */
    protected $productionCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var RankingsHelper */
    protected $rankingsHelper;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var WonderService */
    protected $wonderService;

    /**
     * TickService constructor.
     */
    public function __construct()
    {
        $this->now = now();
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->heroBattleService = app(HeroBattleService::class);
        $this->heroTournamentService = app(HeroTournamentService::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->opsCalculator = app(OpsCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->rankingsHelper = app(RankingsHelper::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->wonderService = app(WonderService::class);
    }

    /**
     * Trigger an hourly tick on all active dominions.
     *
     * @throws Exception|Throwable
     */
    public function tickHourly()
    {
        Log::debug('Hourly tick started');

        // Hourly tick
        $activeRounds = Round::active()->get();

        foreach ($activeRounds as $round) {
            // Reset Daily Bonuses
            if ($round->start_date->hour == now()->hour) {
                $round->activeDominions()->where('protection_ticks_remaining', 0)->update([
                    'daily_platinum' => false,
                    'daily_land' => false,
                    'daily_actions' => AutomationService::DAILY_ACTIONS,
                ], [
                    'event' => 'tick',
                ]);
            }

            $this->performTick($round);
            $this->expireWars($round);
            $this->checkForAbandonedDominions($round);
            $this->heroBattleService->processBattles($round);
            $this->heroTournamentService->processTournaments($round);
        }

        // Realm Assignment
        $rounds = Round::readyForAssignment()->get();
        foreach ($rounds as $round) {
            $realmFinderService = app(\OpenDominion\Services\RealmFinderService::class);
            $realmFinderService->assignRealms($round);
        }

        // Generate Non-Player Dominions
        $rounds = Round::activeSoon()->get();
        foreach ($rounds as $round) {
            $dominionFactory = app(\OpenDominion\Factories\DominionFactory::class);
            $aiHelper = app(\OpenDominion\Helpers\AIHelper::class);
            $filesystem = app(\Illuminate\Filesystem\Filesystem::class);
            $names_json = json_decode($filesystem->get(base_path('app/data/dominion_names.json')));
            $names = collect($names_json->dominion_names);
            $races = Race::where('playable', true)->get();
            $realm = $round->realms()->where('number', 0)->first();
            // Number of NPDs to spawn (60% of the number of real players)
            $npdCount = round($round->dominions()->count() * 0.6);
            for ($cnt=0; $cnt<$npdCount; $cnt++) {
                // Select race
                if ($realm->alignment != 'neutral') {
                    $race = $races->where('alignment', $realm->alignment)->random();
                } else {
                    $race = $races->random();
                }
                // Calculate size
                if ($cnt < $npdCount * 0.6) {
                    // 70% of NPDs between 400 and 525
                    // Standard distribution centered on 487.5 (650a EG)
                    //   with a standard deviation of 37.5 (600a EG - 700a EG)
                    // Outliers set to exactly 525 (~10-15% of all NPDs)
                    $landSize = (int) random_distribution(487.5, 37.5);
                    if ($landSize < 400 || $landSize > 525) {
                        $landSize = 525;
                    }
                } else {
                    // Remaining 30% of NPDs between 526-600
                    // These spawn with increased defense
                    $landSize = mt_rand(526, 600);
                }
                $dominion = null;
                $failCount = 0;
                while ($dominion == null && $failCount < 3) {
                    $rulerName = $names->random();
                    $dominionName = $names->random();
                    if (strlen($rulerName) > strlen($dominionName)) {
                        $swap = $rulerName;
                        $rulerName = $dominionName;
                        $dominionName = $swap;
                    }
                    $dominion = $dominionFactory->createNonPlayer($realm, $race, $rulerName, $dominionName, $landSize);
                    if ($dominion) {
                        // Tick ahead
                        $this->precalculateTick($dominion);
                        $this->performTick($round, $dominion);
                    } else {
                        $failCount++;
                    }
                }
            }
            // Generate NPD instructions
            $npds = $round->dominions()->bot()->get();
            foreach ($npds as $npd) {
                $npd->ai_enabled = true;
                $npd->ai_config = $aiHelper->generateConfig($npd->race);
                $npd->save();
            }
        }

        Log::debug('Hourly tick finished');
    }

    /**
     * Does an hourly tick on an array of dominions.
     *
     * @throws Exception|Throwable
     */
    public function performTick(Round $round, Dominion $dominion = null)
    {
        if ($dominion == null) {
            $where = [
                ['round_id', '=', $round->id],
                ['protection_ticks_remaining', '=', 0],
                ['locked_at', '=', null]
            ];
        } else {
            $where = ['dominions.id' => $dominion->id];
        }

        DB::transaction(function () use ($where) {
            // Update dominions
            DB::table('dominions')
                ->join('dominion_tick', 'dominions.id', '=', 'dominion_tick.dominion_id')
                ->where($where)
                ->where(function ($query) {
                    $query->where('abandoned_at', null)->orWhere('abandoned_at', '>', $this->now);
                })
                ->update([
                    'dominions.prestige' => DB::raw('dominions.prestige + dominion_tick.prestige'),
                    'dominions.peasants' => DB::raw('dominions.peasants + dominion_tick.peasants'),
                    'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                    'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                    'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                    'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),
                    'dominions.resilience' => DB::raw('dominions.resilience + dominion_tick.resilience'),
                    'dominions.fireball_meter' => DB::raw('dominions.fireball_meter + dominion_tick.fireball_meter'),
                    'dominions.lightning_bolt_meter' => DB::raw('dominions.lightning_bolt_meter + dominion_tick.lightning_bolt_meter'),
                    'dominions.resource_platinum' => DB::raw('dominions.resource_platinum + dominion_tick.resource_platinum'),
                    'dominions.resource_food' => DB::raw('dominions.resource_food + dominion_tick.resource_food'),
                    'dominions.resource_lumber' => DB::raw('dominions.resource_lumber + dominion_tick.resource_lumber'),
                    'dominions.resource_mana' => DB::raw('dominions.resource_mana + dominion_tick.resource_mana'),
                    'dominions.resource_ore' => DB::raw('dominions.resource_ore + dominion_tick.resource_ore'),
                    'dominions.resource_gems' => DB::raw('dominions.resource_gems + dominion_tick.resource_gems'),
                    'dominions.resource_tech' => DB::raw('dominions.resource_tech + dominion_tick.resource_tech'),
                    'dominions.resource_boats' => DB::raw('dominions.resource_boats + dominion_tick.resource_boats'),
                    'dominions.improvement_science' => DB::raw('dominions.improvement_science + dominion_tick.improvement_science'),
                    'dominions.improvement_keep' => DB::raw('dominions.improvement_keep + dominion_tick.improvement_keep'),
                    'dominions.improvement_forges' => DB::raw('dominions.improvement_forges + dominion_tick.improvement_forges'),
                    'dominions.improvement_walls' => DB::raw('dominions.improvement_walls + dominion_tick.improvement_walls'),
                    'dominions.military_draftees' => DB::raw('dominions.military_draftees + dominion_tick.military_draftees'),
                    'dominions.military_unit1' => DB::raw('dominions.military_unit1 + dominion_tick.military_unit1'),
                    'dominions.military_unit2' => DB::raw('dominions.military_unit2 + dominion_tick.military_unit2'),
                    'dominions.military_unit3' => DB::raw('dominions.military_unit3 + dominion_tick.military_unit3'),
                    'dominions.military_unit4' => DB::raw('dominions.military_unit4 + dominion_tick.military_unit4'),
                    'dominions.military_spies' => DB::raw('dominions.military_spies + dominion_tick.military_spies'),
                    'dominions.military_assassins' => DB::raw('dominions.military_assassins + dominion_tick.military_assassins'),
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
                    'dominions.stat_total_boat_production' => DB::raw('dominions.stat_total_boat_production + dominion_tick.resource_boat_production'),
                    'dominions.stat_total_food_decay' => DB::raw('dominions.stat_total_food_decay + dominion_tick.resource_food_decay'),
                    'dominions.stat_total_lumber_decay' => DB::raw('dominions.stat_total_lumber_decay + dominion_tick.resource_lumber_decay'),
                    'dominions.stat_total_mana_decay' => DB::raw('dominions.stat_total_mana_decay + dominion_tick.resource_mana_decay'),
                    'dominions.highest_land_achieved' => DB::raw('dominions.highest_land_achieved + dominion_tick.highest_land_achieved'),
                    'dominions.calculated_networth' => DB::raw('dominion_tick.calculated_networth'),
                    'dominions.last_tick_at' => $this->now,
                ]);

            // Update spells
            DB::table('dominion_spells')
                ->join('dominions', 'dominion_spells.dominion_id', '=', 'dominions.id')
                ->where($where)
                ->update([
                    'duration' => DB::raw('`duration` - 1'),
                    'dominion_spells.updated_at' => $this->now,
                ]);

            // Update queues
            DB::table('dominion_queue')
                ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
                ->where($where)
                ->update([
                    'hours' => DB::raw('`hours` - 1'),
                    'dominion_queue.updated_at' => $this->now,
                ]);
        }, 5);

        $this->now = now();

        if ($dominion == null) {
            Log::info(sprintf(
                'Ticked %s dominions in %s ms in %s',
                number_format($round->activeDominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));
        }

        if ($dominion == null) {
            $dominions = $round->activeDominions()
                ->with([
                    'queues',
                    'race',
                    'race.perks',
                    'race.units',
                    'race.units.perks',
                    'spells',
                    'techs',
                    'techs.perks',
                    'tick',
                    'user',
                ])
                ->get();
        } else {
            $dominions = collect([$dominion]);
        }

        $this->performSpellEffects($dominions->pluck('id')->all());

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

                $this->precalculateTick($dominion, true);
            }, 5);

            $this->notificationService->sendNotifications($dominion, 'hourly_dominion');
        }

        $this->now = now();

        if ($dominion == null) {
            Log::info(sprintf(
                'Cleaned up queues, sent notifications, and precalculated %s dominions in %s ms in %s',
                number_format($round->activeDominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));
        }
    }

    /**
     * Checks for wars beyond maximum duration and cancels them.
     *
     * @throws Exception|Throwable
     */
    public function expireWars(Round $round)
    {
        $expiry = now()->subHours(GovernmentService::WAR_MAXIMUM_DURATION)->endOfHour();
        $expiredWars = $round->wars()
            ->where('realm_wars.inactive_at', null)
            ->where('realm_wars.created_at', '<', $expiry)
            ->get();

        foreach ($expiredWars as $war) {
            GameEvent::create([
                'round_id' => $round->id,
                'source_type' => Realm::class,
                'source_id' => $war->source_realm_id,
                'target_type' => RealmWar::class,
                'target_id' => $war->id,
                'type' => 'war_canceled',
            ]);

            $war->source_realm_name_end = $war->sourceRealm->name;
            $war->target_realm_name_end = $war->targetRealm->name;
            $war->inactive_at = now()->addHours(GovernmentService::WAR_INACTIVE_WAIT_IN_HOURS)->startOfHour();
            $war->save();
        }
    }

    /**
     * Checks for dominions whose abandonment wait period has expired.
     *
     * @throws Exception|Throwable
     */
    public function checkForAbandonedDominions(Round $round)
    {
        $abandonedDominions = $round->dominions()
            ->where('abandoned_at', now()->startOfHour());

        $abandonedDominions->update(['monarchy_vote_for_dominion_id' => null]);

        foreach ($abandonedDominions->get() as $dominion) {
            \OpenDominion\Models\GameEvent::create([
                'round_id' => $round->id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'type' => 'abandoned',
            ]);
        }
    }

    /**
     * Reverts an hourly on a dominion.
     *
     * @throws Exception|Throwable
     */
    public function revertTick(Dominion $dominion)
    {
        $lastRestart = $dominion->history()
            ->where('event', 'restart')
            ->orderByDesc('created_at')
            ->first();

        if ($lastRestart !== null) {
            // Only revert the latest tick since the last restart
            $ticks = $dominion->history()
                ->where('event', 'tick')
                ->where('created_at', '>', $lastRestart->created_at)
                ->orderByDesc('created_at')
                ->get();
        } else {
            $ticks = $dominion->history()
                ->where('event', 'tick')
                ->orderByDesc('created_at')
                ->get();
        }

        if ($ticks->count() > 1) {
            // Revert to the tick prior
            $revertTo = $ticks[1]->created_at->toDateTimeLocalString('millisecond');
        } elseif ($lastRestart !== null) {
            // Revert to the last restart
            $revertTo = $lastRestart->created_at->toDateTimeLocalString('millisecond');
        } else {
            // Revert the first tick
            $revertTo = $dominion->created_at;
        }

        $actions = $dominion->history()
            ->where('created_at', '>', $revertTo)
            ->orderByDesc('created_at')
            ->get();

        if (!$actions->count()) {
            return false;
        }

        DB::transaction(function () use ($dominion, $actions, $revertTo) {
            // Update attributes
            foreach ($actions as $action) {
                foreach ($action->delta as $key => $value) {
                    if ($key == 'calculated_networth') {
                        continue;
                    }

                    if ($key == 'expiring_spells') {
                        // Queued Spells
                        foreach (json_decode($value) as $spell_id) {
                            DB::table('dominion_spells')
                                ->updateOrInsert([
                                    'dominion_id' => $dominion->id,
                                    'spell_id' => $spell_id
                                ], [
                                    'duration' => 0,
                                    'cast_by_dominion_id' => $dominion->id,
                                    'created_at' => $this->now,
                                    'updated_at' => $this->now,
                                ]);
                        }
                    }

                    if (isset($dominion->{$key})) {
                        $type = gettype($value);
                        if ($type == 'boolean') {
                            $dominion->{$key} = !$value;
                        } else {
                            $dominion->{$key} -= $value;
                        }

                        if ($action->event == 'tick') {
                            // Queued Resources
                            if (substr($key, 0, 5) == 'land_') {
                                $this->queueService->queueResources('exploration', $dominion, [$key => $value], 0);
                            }
                            if (substr($key, 0, 9) == 'building_') {
                                $this->queueService->queueResources('construction', $dominion, [$key => $value], 0);
                            }
                            if (substr($key, 0, 9) == 'military_') {
                                if ($key == 'military_draftees') {
                                    continue;
                                }
                                // Starvation
                                $unitsStarved = 0;
                                if (isset($action->delta['starvation_casualties'])) {
                                    $casualties = json_decode($action->delta['starvation_casualties']);
                                    if (isset($casualties->{$key})) {
                                        $unitsStarved = $casualties->{$key};
                                    }
                                }
                                $this->queueService->queueResources('training', $dominion, [$key => ($value + $unitsStarved)], 0);
                            }
                        }
                    }

                    if ($action->event == 'tick') {
                        $statMapping = [
                            'resource_platinum' => 'stat_total_platinum_production',
                            'resource_food_production' => 'stat_total_food_production',
                            'resource_lumber_production' => 'stat_total_lumber_production',
                            'resource_mana_production' => 'stat_total_mana_production',
                            'resource_ore' => 'stat_total_ore_production',
                            'resource_gems' => 'stat_total_gem_production',
                            'resource_tech' => 'stat_total_tech_production',
                            'resource_boat_production' => 'stat_total_boat_production',
                            'resource_food_decay' => 'stat_total_food_decay',
                            'resource_lumber_decay' => 'stat_total_lumber_decay',
                            'resource_mana_decay' => 'stat_total_mana_decay'
                        ];
                        if (isset($statMapping[$key])) {
                            $dominion->{$statMapping[$key]} -= $value;
                        }
                    }
                }

                if ($action->event == 'cast spell' && isset($action->delta['queue']['active_spells'])) {
                    foreach ($action->delta['queue']['active_spells'] as $key => $duration) {
                        $spell = Spell::firstWhere('key', $key);
                        if ($spell) {
                            // Update spells that were refreshed early
                            DB::table('dominion_spells')
                                ->where('dominion_id', $dominion->id)
                                ->where('spell_id', $spell->id)
                                ->update([
                                    'duration' => $duration - 1,
                                    'updated_at' => $this->now,
                                ]);
                        }
                    }
                }

                if ($action->event == 'tech' && isset($action->delta['action'])) {
                    // Remove unlocked techs
                    $tech = $dominion->techs->where('key', $action->delta['action'])->first();
                    if ($tech !== null) {
                        DB::table('dominion_techs')
                            ->where('dominion_id', $dominion->id)
                            ->where('tech_id', $tech->id)
                            ->delete();
                    }
                }

                $action->delete();
            }

            // Update spells
            DB::table('dominion_spells')
                ->where('dominion_id', $dominion->id)
                ->update([
                    'duration' => DB::raw('`duration` + 1'),
                    'updated_at' => $this->now,
                ]);

            // Delete spells
            DB::table('dominion_spells')
                ->where('dominion_id', $dominion->id)
                ->where('duration', '>=', 12)
                ->delete();

            // Update queues - two step since MySQL does not support deferred constraints
            DB::table('dominion_queue')
                ->where('dominion_id', $dominion->id)
                ->update([
                    'hours' => DB::raw('`hours` + 13'),
                    'updated_at' => $this->now,
                ]);

            DB::table('dominion_queue')
                ->where('dominion_id', $dominion->id)
                ->update([
                    'hours' => DB::raw('`hours` - 12'),
                    'updated_at' => $this->now,
                ]);

            // Delete queues
            DB::table('dominion_queue')
                ->where('dominion_id', $dominion->id)
                ->where('hours', '>=', 12)
                ->delete();

            DB::table('dominion_queue')
                ->where('dominion_id', $dominion->id)
                ->where('hours', '>=', 9)
                ->where('source', 'training')
                ->whereIn('resource', ['military_unit1', 'military_unit2'])
                ->delete();

            $dominion->last_tick_at = $this->now;
            $dominion->save(['protection' => true]);

            foreach ($dominion->notifications->where('created_at', '>', $revertTo) as $notification) {
                // Erase notifications
                $notification->delete();
            }
        }, 5);

        return true;
    }

    /**
     * Does a daily tick on all active dominions and rounds.
     *
     * @throws Exception|Throwable
     */
    public function tickDaily()
    {
        foreach (Round::with('dominions')->active()->get() as $round) {
            // Only runs once daily
            if ($round->start_date->hour != now()->hour) {
                continue;
            }

            // Move Inactive Dominions
            // toBase required to prevent ambiguous updated_at column in query
            $graveyardRealm = $round->realms()->where('number', 0)->first();
            if ($graveyardRealm !== null) {
                $inactiveDominions = $round->dominions()
                    ->join('users', 'dominions.user_id', '=', 'users.id')
                    ->where('realms.number', '>', 0)
                    ->where('dominions.protection_ticks_remaining', '>', 0)
                    ->where('dominions.created_at', '<', now()->subDays(3))
                    ->where('users.last_online', '<', now()->subDays(3))
                    ->toBase()->update([
                        'realm_id' => $graveyardRealm->id,
                        'monarchy_vote_for_dominion_id' => null
                    ]);
            }

            // Sentient Wonders
            // $this->wonderService->handleSentience($round);

            // Spawn Wonders
            $day = $round->daysInRound();
            if ($day > 1 && $day % 2 == 0) {
                $this->wonderService->createWonder($round);
            }
        }
    }

    protected function performSpellEffects(array $dominionIds)
    {
        DB::transaction(function () use ($dominionIds) {
            // Convert status effects
            $burningSpell = Spell::where('key', 'burning')->first();
            $lightningStormSpell = Spell::where('key', 'lightning_storm')->first();
            $rejuvenationSpell = Spell::where('key', 'rejuvenation')->first();

            if (($burningSpell || $lightningStormSpell) && $rejuvenationSpell) {
                DB::table('dominion_spells')
                    ->whereIn('dominion_id', $dominionIds)
                    ->whereIn('spell_id', [$burningSpell->id, $lightningStormSpell->id])
                    ->where('duration', '<=', 0)
                    ->update([
                        'spell_id' => $rejuvenationSpell->id,
                        'duration' => $rejuvenationSpell->duration
                    ]);
            }

            // Special case for Cull the Weak
            $upgradePerk = SpellPerkType::where('key', 'upgrade_specs')->first();
            if ($upgradePerk !== null) {
                $baseConversion = 2;
                $landMultiplier = 1/750;
                $spellIds = $upgradePerk->spells->pluck('id');
                $dominionSpells = DominionSpell::whereIn('spell_id', $spellIds)->whereIn('dominion_id', $dominionIds)->get();
                foreach ($dominionSpells as $dominionSpell) {
                    $totalLand = $this->landCalculator->getTotalLand($dominionSpell->dominion);
                    $conversions = rfloor($baseConversion + ($totalLand * $landMultiplier));
                    $unit1 = min($conversions, $dominionSpell->dominion->military_unit1);
                    $unit2 = min($conversions, $dominionSpell->dominion->military_unit2);
                    // Queue units
                    $units = [
                        'military_unit3' => $unit2,
                        'military_unit4' => $unit1,
                    ];
                    $this->queueService->queueResources('training', $dominionSpell->dominion, $units);
                    // Update dominion
                    $newUnit1 = $dominionSpell->dominion->military_unit1 - $unit1;
                    $newUnit2 = $dominionSpell->dominion->military_unit2 - $unit2;
                    $dominionSpell->dominion->update([
                        'military_unit1' => $newUnit1,
                        'military_unit2' => $newUnit2
                    ]);
                }
            }

            // Special case for Death and Decay
            $convertPerk = SpellPerkType::where('key', 'convert_peasants_to_self_military_unit1')->first();
            if ($convertPerk !== null) {
                $spellIds = $convertPerk->spells->pluck('id');
                $dominionSpells = DominionSpell::whereIn('spell_id', $spellIds)->whereIn('dominion_id', $dominionIds)->get();
                foreach ($dominionSpells as $dominionSpell) {
                    $perk = $dominionSpell->spell->perks()->where('key', 'convert_peasants_to_self_military_unit1')->first();
                    $conversions = $dominionSpell->dominion->peasants * ($perk->pivot->value / 100);
                    // Queue units
                    $units = ['military_unit1' => $conversions];
                    $this->queueService->queueResources('training', $dominionSpell->dominion, $units);
                    // Update dominion
                    $newPeasants = $dominionSpell->dominion->peasants - $conversions;
                    $dominionSpell->dominion->update(['peasants' => $newPeasants]);
                }
            }

            // Special case for Dark Elf
            $unitProductionPerk = SpellPerkType::where('key', 'wizard_guilds_produce_military_unit3')->first();
            if ($unitProductionPerk !== null) {
                $spellIds = $unitProductionPerk->spells->pluck('id');
                $dominionSpells = DominionSpell::whereIn('spell_id', $spellIds)->whereIn('dominion_id', $dominionIds)->get();
                foreach ($dominionSpells as $dominionSpell) {
                    $perk = $dominionSpell->spell->perks()->where('key', 'wizard_guilds_produce_military_unit3')->first();
                    $unitsProduced = ($dominionSpell->dominion->building_wizard_guild * $perk->pivot->value) + $dominionSpell->dominion->racial_value;
                    // Queue units
                    $units = ['military_unit3' => (int)rfloor($unitsProduced)];
                    $this->queueService->queueResources('training', $dominionSpell->dominion, $units);
                    // Update dominion
                    $dominionSpell->dominion->update(['racial_value' => fmod($unitsProduced, 1)]);
                }
            }
        }, 5);
    }

    protected function cleanupActiveSpells(Dominion $dominion)
    {
        $finished = DominionSpell::query()
            ->where('dominion_id', $dominion->id)
            ->where('duration', '<=', 0)
            ->get()
            ->map(function ($dominionSpell) {
                return $dominionSpell->spell;
            });

        $beneficialSpells = $finished->filter(function ($spell) {
            return !$spell->isHarmful();
        })->toArray();

        $harmfulSpells = $finished->filter(function ($spell) {
            return $spell->isHarmful();
        })->toArray();

        if (!empty($beneficialSpells)) {
            $this->notificationService->queueNotification('beneficial_magic_dissipated', $beneficialSpells);
        }

        if (!empty($harmfulSpells)) {
            $this->notificationService->queueNotification('harmful_magic_dissipated', $harmfulSpells);
        }

        DB::table('dominion_spells')
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
            if ($source === 'operations') continue;

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
                        'updated_at'
                    ], true) &&
                    ((gettype($value) == 'string' && $value !== '[]') || ($value != 0))
                );
            }, ARRAY_FILTER_USE_BOTH);

            $dominionHistoryService->record($dominion, $changes, HistoryService::EVENT_TICK);
        }

        /* These calculators need to ignore queued resources for the following tick */
        $this->casualtiesCalculator->setForTick(true);
        $this->militaryCalculator->setForTick(true);
        $this->networthCalculator->setForTick(true);
        $this->populationCalculator->setForTick(true);
        $this->queueService->setForTick(true);

        // Reset tick values
        foreach ($tick->getAttributes() as $attr => $value) {
            if (!in_array($attr, ['id', 'dominion_id', 'updated_at', 'starvation_casualties', 'expiring_spells'], true)) {
                $tick->{$attr} = 0;
            } elseif ($attr === 'starvation_casualties' || $attr === 'expiring_spells') {
                $tick->{$attr} = [];
            }
        }

        // Hacky refresh for dominion
        $dominion->refresh()->load([
            'race',
            'race.perks',
            'race.units',
            'race.units.perks',
            'techs',
            'techs.perks',
        ]);

        // Active spells
        $this->spellCalculator->getActiveSpells($dominion);

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

        // Prestige capped at land size
        $prestigeCap = max($totalLand, 250);
        if ($dominion->prestige > $prestigeCap) {
            $tick->prestige -= ($dominion->prestige - $prestigeCap);
        }

        // Population
        $drafteesGrowthRate = $this->populationCalculator->getPopulationDrafteeGrowth($dominion);
        $populationPeasantGrowth = $this->populationCalculator->getPopulationPeasantGrowth($dominion);

        $tick->peasants = $populationPeasantGrowth;
        $tick->military_draftees = $drafteesGrowthRate;

        // Resources
        $tick->resource_platinum += $this->productionCalculator->getPlatinumProduction($dominion);
        $tick->resource_lumber_production += $this->productionCalculator->getLumberProduction($dominion);
        $tick->resource_lumber_decay += $this->productionCalculator->getLumberDecay($dominion);
        $tick->resource_lumber += $this->productionCalculator->getLumberNetChange($dominion);
        $tick->resource_mana_production += $this->productionCalculator->getManaProduction($dominion);
        $tick->resource_mana_decay += $this->productionCalculator->getManaDecay($dominion);
        $tick->resource_mana += $this->productionCalculator->getManaNetChange($dominion);
        $tick->resource_ore += $this->productionCalculator->getOreProduction($dominion);
        $tick->resource_gems += $this->productionCalculator->getGemProduction($dominion);
        $tick->resource_tech += $this->productionCalculator->getTechProduction($dominion);
        $tick->resource_boats += $this->productionCalculator->getBoatProduction($dominion);
        $tick->resource_boat_production += $this->productionCalculator->getBoatProduction($dominion);
        $tick->resource_food_production += $this->productionCalculator->getFoodProduction($dominion);
        $tick->resource_food_decay += $this->productionCalculator->getFoodDecay($dominion);
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

            if (-$tick->peasants > $dominion->peasants) {
                $tick->peasants = -$dominion->peasants;
            }

            // Decrement to zero
            $tick->resource_food = -$dominion->resource_food;
        } else {
            // Food production
            $tick->resource_food += $foodNetChange;
        }

        // Morale
        $tick->morale = $dominion->getMoraleGain();

        // Spy Strength
        if ($dominion->spy_strength < 100) {
            $spyStrengthAdded = $this->militaryCalculator->getSpyStrengthRegen($dominion);
            $tick->spy_strength = min($spyStrengthAdded, 100 - $dominion->spy_strength);
        }

        // Wizard Strength
        if ($dominion->wizard_strength < 100) {
            $wizardStrengthAdded = $this->militaryCalculator->getWizardStrengthRegen($dominion);
            $tick->wizard_strength = min($wizardStrengthAdded, 100 - $dominion->wizard_strength);
        }

        // Resilience
        $tick->resilience += $this->opsCalculator->getResilienceDecay($dominion);
        $tick->fireball_meter += $this->opsCalculator->getSpellMeterDecay($dominion, 'fireball');
        $tick->lightning_bolt_meter += $this->opsCalculator->getSpellMeterDecay($dominion, 'lightning_bolt');

        // Store highest land total
        if ($totalLand > $dominion->highest_land_achieved) {
            $tick->highest_land_achieved += $totalLand - $dominion->highest_land_achieved;
        }

        // Calculate networth
        $tick->calculated_networth = $this->networthCalculator->getDominionNetworth($dominion, true);

        foreach ($incomingQueue as $row) {
            // Reset current resources in case object is saved later
            $dominion->{$row->resource} -= $row->amount;
        }

        // Expiring spells
        $tick->expiring_spells = DB::table('dominion_spells')
            ->where('dominion_id', $dominion->id)
            ->where('duration', '<=', 1)
            ->pluck('spell_id');

        $tick->save();

        $this->casualtiesCalculator->setForTick(false);
        $this->militaryCalculator->setForTick(false);
        $this->networthCalculator->setForTick(false);
        $this->populationCalculator->setForTick(false);
        $this->queueService->setForTick(false);
    }

    public function updateDailyRankings(): void
    {
        // Update rankings
        $activeRounds = Round::activeRankings()->get();

        foreach ($activeRounds as $round) {
            // Only run once daily
            if ($round->start_date->hour != now()->hour) {
                continue;
            }

            Log::debug('Daily rankings started');

            $activeDominions = $round->dominions()->with([
                'race',
                'realm',
            ])->get();

            // Calculate current statistics
            $statistics = [];
            foreach ($activeDominions as $dominion) {
                $isAbandoned = ($dominion->abandoned_at !== null && $dominion->abandoned_at < now());
                $isLocked = $dominion->locked_at !== null;

                foreach ($this->rankingsHelper->getRankings() as $ranking) {

                    if ($ranking['stat'] == 'land') {
                        $value = $this->landCalculator->getTotalLand($dominion);
                    } elseif ($ranking['stat'] == 'networth') {
                        $value = $this->networthCalculator->getDominionNetworth($dominion);
                    } elseif ($ranking['stat'] == 'land_explored') {
                        $value = max(0, $dominion->stat_total_land_explored - $dominion->stat_total_land_lost);
                    } elseif ($ranking['stat'] == 'land_conquered') {
                        $value = max(0, $dominion->stat_total_land_conquered - $dominion->stat_total_land_lost);
                    } else {
                        $value = $dominion->{$ranking['stat']};
                    }

                    $zeroOutRank = false;
                    if($value != 0 && ($isAbandoned || $isLocked)) {
                        $value = 0;
                        $zeroOutRank = true;
                    }

                    if ($value != 0 || $zeroOutRank) {
                        $statistics[] = [
                            'round_id' => $round->id,
                            'dominion_id' => $dominion->id,
                            'dominion_name' => $dominion->name,
                            'race_name' => $dominion->race->name,
                            'realm_number' => $dominion->realm->number,
                            'realm_name' => $dominion->realm->name,
                            'key' => $ranking['key'],
                            'value' => $value,
                        ];
                    }
                }
            }

            // Saving current statistics
            DB::table('daily_rankings')->upsert(
                $statistics,
                ['dominion_id', 'key'],
                ['dominion_name', 'race_name', 'realm_number', 'realm_name', 'value'],
            );

            // Calculate ranks
            $ranks = DB::table('daily_rankings AS a')
                ->select(DB::raw('a.*, COUNT(b.value)+1 AS new_rank'))
                ->leftJoin('daily_rankings AS b', function ($join) use ($round) {
                    $join->on('a.value', '<', 'b.value');
                    $join->on('a.key', '=', 'b.key');
                    $join->where('b.round_id', $round->id);
                })
                ->where('a.round_id', $round->id)
                ->groupBy('a.dominion_id', 'a.key', 'a.value')
                ->orderBy('new_rank')
                ->get()
                ->map(function ($obj) {
                    $obj->previous_rank = $obj->rank;
                    $obj->rank = $obj->new_rank;
                    unset($obj->new_rank);
                    return (array) $obj;
                })
                ->toArray();

            // Update ranks
            DB::table('daily_rankings')->upsert(
                $ranks,
                ['dominion_id', 'key'],
                ['rank', 'previous_rank'],
            );

            Log::debug('Daily rankings finished');

            // Update Valor rankings
            Log::debug('Valor calculation started');
            $valorService = app(ValorService::class);
            $valorService->updateValor($round);
            Log::debug('Valor calculation finished');
        }
    }
}
