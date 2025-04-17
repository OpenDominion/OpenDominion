<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Models\DailyRanking;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\User;

class ValhallaController extends AbstractController
{
    public function getIndex()
    {
        $leagues = RoundLeague::with('rounds')->orderByDesc('created_at')->get();

        return view('pages.valhalla.index', [
            'leagues' => $leagues,
        ]);
    }

    public function getRound(Round $round)
    {
        if ($response = $this->guardAgainstActiveRound($round)) {
            return $response;
        }

        $races = $round->dominions
            ->sortBy('race.name')
            ->pluck('race.name', 'race.key')
            ->unique();

        return view('pages.valhalla.round', [
            'round' => $round,
            'races' => $races,
        ]);
    }

    public function getRoundType(Round $round, string $type)
    {
        if ($response = $this->guardAgainstActiveRound($round)) {
            return $response;
        }

        // todo: refactor

        $headers = [
            '#' => ['width' => 50, 'align-center' => true],
            'player' => ['width' => 150, 'align-center' => true],
            'players' => ['align-center' => true],
            'race' => ['width' => 100, 'align-center' => true],
            'realm' => ['width' => 100, 'align-center' => true],
            'alignment' => ['width' => 100, 'align-center' => true],
            'number' => ['width' => 50, 'align-center' => true],
            'networth' => ['width' => 150, 'align-center' => true],
            'avg_networth' => ['width' => 150, 'align-center' => true],
            'land' => ['width' => 150, 'align-center' => true],
            'avg_land' => ['width' => 150, 'align-center' => true],
            'value' => ['width' => 100, 'align-center' => true],
        ];

        switch ($type) {
            case 'strongest-dominions': $data = $this->getDominionsByRanking($round, 'strongest-dominions'); break;
            case 'strongest-good-dominions': $data = $this->getStrongestDominions($round, null, 'good'); break;
            case 'strongest-evil-dominions': $data = $this->getStrongestDominions($round, null, 'evil'); break;
            case 'strongest-realms': $data = $this->getStrongestRealms($round); break;
            case 'strongest-good-realms': $data = $this->getStrongestRealms($round, 'good'); break;
            case 'strongest-evil-realms': $data = $this->getStrongestRealms($round, 'evil'); break;
            case 'strongest-packs': $data = $this->getStrongestPacks($round); break;
            case 'strongest-solo': $data = $this->getDominionsByRanking($round, 'strongest-dominions', true); break;
            case 'largest-dominions': $data = $this->getDominionsByRanking($round, 'largest-dominions'); break;
            case 'largest-good-dominions': $data = $this->getLargestDominions($round, null, 'good'); break;
            case 'largest-evil-dominions': $data = $this->getLargestDominions($round, null, 'evil'); break;
            case 'largest-realms': $data = $this->getLargestRealms($round); break;
            case 'largest-good-realms': $data = $this->getLargestRealms($round, 'good'); break;
            case 'largest-evil-realms': $data = $this->getLargestRealms($round, 'evil'); break;
            case 'largest-packs': $data = $this->getLargestPacks($round); break;
            case 'largest-solo': $data = $this->getDominionsByRanking($round, 'largest-dominions', true); break;
            case 'stat-prestige': $data = $this->getDominionsByRanking($round, 'prestige'); break;
            case 'stat-bounties-collected': $data = $this->getDominionsByRanking($round, 'bounties-collected'); break;
            case 'stat-spies-charmed': $data = $this->getDominionsByRanking($round, 'spies-charmed'); break;
            case 'stat-attacking-success': $data = $this->getDominionsByRanking($round, 'attacking-success'); break;
            case 'stat-defending-success': $data = $this->getDominionsByStatistic($round, 'stat_defending_success'); break;
            case 'stat-espionage-success': $data = $this->getDominionsByRanking($round, 'espionage-success'); break;
            case 'stat-spell-success': $data = $this->getDominionsByRanking($round, 'spell-success'); break;
            case 'stat-spy-mastery': $data = $this->getDominionsByRanking($round, 'spy-mastery'); break;
            case 'stat-wizard-mastery': $data = $this->getDominionsByRanking($round, 'wizard-mastery'); break;
            case 'stat-spies-executed': $data = $this->getDominionsByRanking($round, 'spies-executed'); break;
            case 'stat-wizards-executed': $data = $this->getDominionsByRanking($round, 'wizards-executed'); break;
            //case 'stat-total-platinum-production': $data = $this->getDominionsByStatistic($round, 'stat_total_platinum_production'); break;
            //case 'stat-total-food-production': $data = $this->getDominionsByStatistic($round, 'stat_total_food_production'); break;
            //case 'stat-total-lumber-production': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_production'); break;
            //case 'stat-total-mana-production': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_production'); break;
            //case 'stat-total-ore-production': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_production'); break;
            //case 'stat-total-gem-production': $data = $this->getDominionsByStatistic($round, 'stat_total_gem_production'); break;
            //case 'stat-total-tech-production': $data = $this->getDominionsByStatistic($round, 'stat_total_tech_production'); break;
            //case 'stat-total-boat-production': $data = $this->getDominionsByStatistic($round, 'stat_total_boat_production'); break;
            case 'stat-total-land-explored': $data = $this->getDominionsByRanking($round, 'total-land-explored'); break;
            case 'stat-total-land-conquered': $data = $this->getDominionsByRanking($round, 'total-land-conquered'); break;
            case 'stat-total-platinum-stolen': $data = $this->getDominionsByRanking($round, 'platinum-thieves'); break;
            case 'stat-total-food-stolen': $data = $this->getDominionsByRanking($round, 'food-thieves'); break;
            case 'stat-total-lumber-stolen': $data = $this->getDominionsByRanking($round, 'lumber-thieves'); break;
            case 'stat-total-mana-stolen': $data = $this->getDominionsByRanking($round, 'mana-thieves'); break;
            case 'stat-total-ore-stolen': $data = $this->getDominionsByRanking($round, 'ore-thieves'); break;
            case 'stat-total-gems-stolen': $data = $this->getDominionsByRanking($round, 'gem-thieves'); break;
            case 'stat-top-saboteurs': $data = $this->getDominionsByRanking($round, 'saboteurs'); break;
            case 'stat-top-magical-assassins': $data = $this->getDominionsByRanking($round, 'magical-assassins'); break;
            case 'stat-top-military-assassins': $data = $this->getDominionsByRanking($round, 'military-assassins'); break;
            case 'stat-top-snare-setters': $data = $this->getDominionsByRanking($round, 'snare-setters'); break;
            case 'stat-masters-of-fire': $data = $this->getDominionsByRanking($round, 'masters-of-fire'); break;
            case 'stat-masters-of-plague': $data = $this->getDominionsByRanking($round, 'masters-of-plague'); break;
            case 'stat-masters-of-swarm': $data = $this->getDominionsByRanking($round, 'masters-of-swarm'); break;
            case 'stat-masters-of-air': $data = $this->getDominionsByRanking($round, 'masters-of-air'); break;
            case 'stat-masters-of-lightning': $data = $this->getDominionsByRanking($round, 'masters-of-lightning'); break;
            case 'stat-masters-of-water': $data = $this->getDominionsByRanking($round, 'masters-of-water'); break;
            case 'stat-masters-of-earth': $data = $this->getDominionsByRanking($round, 'masters-of-earth'); break;
            case 'stat-top-spy-disbanders': $data = $this->getDominionsByRanking($round, 'spy-disbanders'); break;
            case 'realm-stat-prestige': $data = $this->getRealmsByStatistic($round, 'prestige'); break;
            case 'realm-stat-attacking-success': $data = $this->getRealmsByStatistic($round, 'stat_attacking_success'); break;
            case 'stat-wonder-damage': $data = $this->getDominionsByRanking($round, 'wonder-damage'); break;
            case 'stat-wonders-destroyed': $data = $this->getDominionsByStatistic($round, 'stat_wonders_destroyed'); break;
            case 'realm-stat-wonder-damage': $data = $this->getRealmsByStatistic($round, 'stat_wonder_damage'); break;
            case 'realm-stat-wonders-destroyed': $data = $this->getRealmsByStatistic($round, 'stat_wonders_destroyed'); break;
            case 'realm-stat-total-land-explored': $data = $this->getRealmsByStatistic($round, 'stat_total_land_explored'); break;
            case 'realm-stat-total-land-conquered': $data = $this->getRealmsByStatistic($round, 'stat_total_land_conquered'); break;
            case 'hero-stat-experience': $data = $this->getHeroesByStatistic($round, 'experience'); break;

            default:
                if (!preg_match('/(strongest|largest|stat)-([-\w]+)/', $type, $matches)) {
                    return redirect()->back()
                        ->withErrors(["Valhalla type '{$type}' not supported"]);
                }

                list(, $prefix, $raceKey) = $matches;

                $race = Race::where('key', $raceKey)->firstOrFail();

                if ($prefix === 'strongest') {
                    $data = $this->getStrongestDominions($round, $race);
                } else {
                    $data = $this->getLargestDominions($round, $race);
                }
                break;
        }

        $type = str_replace('stat-', '', $type);

        return view('pages.valhalla.round-type', compact(
            'round',
            'type',
            'headers',
            'data'
        ));
    }

    public function getUser(User $user)
    {
        $landCalculator = app(LandCalculator::class);
        $networthCalculator = app(NetworthCalculator::class);
        $rankingsHelper = app(RankingsHelper::class);

        $dominions = $user->dominions()
            ->with(['queues', 'race.units.perks', 'realm', 'round.league'])
            ->orderByDesc('round_id')
            ->get()
            ->filter(function (Dominion $dominion) {
                if ($dominion->round->end_date < now()) return $dominion;
            });

        $leagues = RoundLeague::with('rounds')
            ->orderByDesc('created_at')
            ->get();

        $dailyRankings = DailyRanking::with('round')
            ->whereIn('dominion_id', $dominions->pluck('id'))
            ->get()
            ->groupBy('round.round_league_id');

        return view('pages.valhalla.user', [
            'user' => $user,
            'dominions' => $dominions,
            'leagues' => $leagues,
            'dailyRankings' => $dailyRankings,
            'landCalculator' => $landCalculator,
            'networthCalculator' => $networthCalculator,
            'rankingsHelper' => $rankingsHelper,
        ]);
    }

    public function getUserSearch(Request $request)
    {
        $search = trim($request->query('query'));

        $users = User::where('display_name', 'LIKE', "%{$search}%")
            ->orderBy('id')
            ->take(50)
            ->get();

        return view('pages.valhalla.user-search', [
            'search' => $search,
            'users' => $users,
        ]);
    }

    public function getLeague(RoundLeague $league)
    {
        $rankingsHelper = app(RankingsHelper::class);

        return view('pages.valhalla.league', [
            'league' => $league,
            'rankingsHelper' => $rankingsHelper,
        ]);
    }

    public function getLeagueType(RoundLeague $league, string $type)
    {
        $rankingsHelper = app(RankingsHelper::class);

        $rankings = $rankingsHelper->getRankings();
        if (!isset($rankings[$type])) {
            return redirect()->back()->withErrors(["Valhalla type '{$type}' not supported"]);
        }

        $rounds = $league->rounds()
            ->where('end_date', '<', now())
            ->get();

        $standings = DailyRanking::with(['dominion.user'])
            ->whereIn('round_id', $rounds->pluck('id'))
            ->where('key', $type)
            ->where('value', '>', 0)
            ->get()
            ->filter(function ($dailyRanking) {
                return $dailyRanking->dominion->user_id !== null;
            })
            ->map(function ($dailyRanking) {
                return [
                    'value' => $dailyRanking->value,
                    'user_id' => $dailyRanking->dominion->user_id,
                    'display_name' => $dailyRanking->dominion->user->display_name,
                ];
            })
            ->groupBy('user_id')
            ->map(function ($userRankings) {
                $firstRanking = $userRankings->first();
                return [
                    'value' => $userRankings->sum('value'),
                    'user_id' => $firstRanking['user_id'],
                    'display_name' => $firstRanking['display_name'],
                ];
            })
            ->sortByDesc('value');

        return view('pages.valhalla.league-type', [
            'league' => $league,
            'ranking' => $rankings[$type],
            'standings' => $standings,
            'rankingsHelper' => $rankingsHelper,
        ]);
    }

    /**
     * @param Round $round
     * @return Response|null
     */
    protected function guardAgainstActiveRound(Round $round)
    {
        if ($round->isActive() || !$round->hasStarted()) {
            return redirect()->back()
                ->withErrors(['Only ended rounds can be viewed in Valhalla']);
        }

        return null;
    }

    protected function getStrongestDominions(Round $round, Race $race = null, ?string $alignment = null)
    {
        $networthCalculator = app(NetworthCalculator::class);

        $builder = $round->dominions()
            ->with(['queues', 'realm', 'race.units.perks', 'user']);

        if ($alignment !== null) {
            $builder->whereHas('race', function ($builder) use ($alignment) {
                $builder->where('alignment', $alignment);
            });
        }

        if ($race !== null) {
            $builder->where('race_id', $race->id);
        }

        return $builder->get()
            ->map(function (Dominion $dominion) use ($networthCalculator, $race) {
                if ($dominion->user) {
                    $player = '<a href="' . route('valhalla.user', $dominion->user->id) . '">' . htmlentities($dominion->user->display_name) . '</a>';
                } else {
                    $player = 'Bot';
                }

                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
                    'player' => $player,
                ];

                if ($race === null) {
                    $data += [
                        'race' => $dominion->race->name,
                    ];
                }

                $data += [
                    'realm' => $dominion->realm->number,
                    'networth' => $networthCalculator->getDominionNetworth($dominion),
                ];

                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['networth'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['networth'] = number_format($row['networth']);
                return $row;
            });
    }

    protected function getStrongestRealms(Round $round, ?string $alignment = null)
    {
        $networthCalculator = app(NetworthCalculator::class);

        $builder = $round->realms()
            ->with(['dominions.queues', 'dominions.race.units', 'dominions.race.units.perks']);

        if ($alignment !== null) {
            $builder->where('alignment', $alignment);
        }

        return $builder->get()
            ->map(function (Realm $realm) use ($networthCalculator) {
                return [
                    '#' => null,
                    'realm name' => $realm->name,
                    'alignment' => ucfirst($realm->alignment),
                    'number' => $realm->number,
                    'networth' => $networthCalculator->getRealmNetworth($realm),
                ];
            })
            ->sortByDesc(function ($row) {
                return $row['networth'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['networth'] = number_format($row['networth']);
                return $row;
            });
    }

    protected function getStrongestPacks(Round $round)
    {
        $networthCalculator = app(NetworthCalculator::class);

        $builder = $round->packs()
            ->with(['dominions.user', 'realm']);

        $builder->has('dominions', '>', 1);

        return $builder->get()
            ->map(function (Pack $pack) use ($networthCalculator) {
                $data = [
                    '#' => null,
                    'pack' => $pack->name,
                    'players' => implode(', ', $pack->dominions
                        ->sortBy('user.display_name')
                        ->pluck('user.display_name')
                        ->all()),
                    'realm' => $pack->realm->number,
                    'avg_networth' => round($pack->dominions
                            ->map(function (Dominion $dominion) use ($networthCalculator) {
                                return $networthCalculator->getDominionNetworth($dominion);
                            })
                            ->reduce(function ($carry, $item) {
                                return ($carry + $item);
                            }) / $pack->dominions->count()),
                ];

                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['avg_networth'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['avg_networth'] = number_format($row['avg_networth']);
                return $row;
            });
    }

    protected function getLargestDominions(Round $round, Race $race = null, ?string $alignment = null)
    {
        $landCalculator = app(LandCalculator::class);

        $builder = $round->dominions()
            ->with(['realm', 'race.units', 'user']);

        if ($alignment !== null) {
            $builder->whereHas('race', function ($builder) use ($alignment) {
                $builder->where('alignment', $alignment);
            });
        }

        if ($race !== null) {
            $builder->where('race_id', $race->id);
        }

        return $builder->get()
            ->map(function (Dominion $dominion) use ($landCalculator, $race) {
                if ($dominion->user) {
                    $player = '<a href="' . route('valhalla.user', $dominion->user->id) . '">' . htmlentities($dominion->user->display_name) . '</a>';
                } else {
                    $player = 'Bot';
                }

                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
                    'player' => $player,
                ];

                if ($race === null) {
                    $data += [
                        'race' => $dominion->race->name,
                    ];
                }

                $data += [
                    'realm' => $dominion->realm->number,
                    'land' => $landCalculator->getTotalLand($dominion),
                ];

                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['land'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['land'] = number_format($row['land']);
                return $row;
            });
    }

    protected function getLargestRealms(Round $round, ?string $alignment = null)
    {
        $landCalculator = app(LandCalculator::class);

        $builder = $round->realms()
            ->with(['dominions.race.units']);

        if ($alignment !== null) {
            $builder->where('alignment', $alignment);
        }

        return $builder->get()
            ->map(function (Realm $realm) use ($landCalculator) {
                return [
                    '#' => null,
                    'realm name' => $realm->name,
                    'alignment' => ucfirst($realm->alignment),
                    'number' => $realm->number,
                    'land' => $realm->dominions->reduce(function ($carry, Dominion $dominion) use ($landCalculator) {
                        return ($carry + $landCalculator->getTotalLand($dominion));
                    }),
                ];
            })
            ->sortByDesc(function ($row) {
                return $row['land'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['land'] = number_format($row['land']);
                return $row;
            });
    }

    protected function getLargestPacks(Round $round)
    {
        $landCalculator = app(LandCalculator::class);

        $builder = $round->packs()
            ->with(['dominions.user', 'realm']);

        $builder->has('dominions', '>', 1);

        return $builder->get()
            ->map(function (Pack $pack) use ($landCalculator) {
                $data = [
                    '#' => null,
                    'pack' => $pack->name,
                    'players' => implode(', ', $pack->dominions
                        ->sortBy('user.display_name')
                        ->pluck('user.display_name')
                        ->all()),
                    'realm' => $pack->realm->number,
                    'avg_land' => round($pack->dominions
                            ->map(function (Dominion $dominion) use ($landCalculator) {
                                return $landCalculator->getTotalLand($dominion);
                            })
                            ->reduce(function ($carry, $item) {
                                return ($carry + $item);
                            }) / $pack->dominions->count()),
                ];

                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['avg_land'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['avg_land'] = number_format($row['avg_land']);
                return $row;
            });
    }

    protected function getDominionsByRanking(Round $round, string $ranking, bool $solo = false)
    {

        $query = DailyRanking::with(['dominion', 'dominion.user'])
            ->where('round_id', $round->id)
            ->where('key', $ranking)
            ->orderByDesc('value')
            ->get();

        if ($solo) {
            $query = $query->filter(function (DailyRanking $ranking) {
                if ($ranking->dominion->pack_id == null && $ranking->dominion->user_id !== null) {
                    return true;
                }
            });
        }

        $query = $query->map(function (DailyRanking $ranking) {
                if ($ranking->dominion->user) {
                    $player = '<a href="' . route('valhalla.user', $ranking->dominion->user->id) . '">' . htmlentities($ranking->dominion->user->display_name) . '</a>';
                } else {
                    $player = 'Bot';
                }
                return [
                    '#' => $ranking->rank,
                    'dominion' => $ranking->dominion->name,
                    'player' => $player,
                    'race' => $ranking->race_name,
                    'realm' => $ranking->realm_number,
                    'value' => number_format($ranking->value),
                ];
            })
            ->take(100)
            ->values();

        if ($solo) {
            $query = $query->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                return $row;
            });
        }

        return $query;
    }

    protected function getDominionsByStatistic(Round $round, string $stat)
    {
        $builder = $round->dominions()
            ->with(['realm', 'race', 'user'])
            ->where($stat, '>', 0);

        return $builder->get()
            ->map(function (Dominion $dominion) use ($stat) {
                if ($dominion->user) {
                    $player = '<a href="' . route('valhalla.user', $dominion->user->id) . '">' . htmlentities($dominion->user->display_name) . '</a>';
                } else {
                    $player = 'Bot';
                }

                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
                    'player' => $player,
                    'race' => $dominion->race->name,
                    'realm' => $dominion->realm->number,
                    'value' => $dominion->{$stat},
                ];
                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['value'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['value'] = number_format($row['value']);
                return $row;
            });
    }

    protected function getRealmsByStatistic(Round $round, string $stat)
    {
        $builder = $round->realms()
            ->with(['dominions'])
            ->where('number', '>', 0);

        return $builder->get()
            ->map(function ($realm) use ($stat) {
                $realm->{$stat} = $realm->dominions
                    ->where('user_id', '!=', null)
                    ->sum($stat);
                return $realm;
            })
            ->filter(function ($realm) use ($stat) {
                if ($realm->{$stat} > 0) {
                    return $realm;
                }
            })
            ->map(function (Realm $realm) use ($stat) {
                $data = [
                    '#' => null,
                    'realm name' => $realm->name,
                    'alignment' => ucfirst($realm->alignment),
                    'number' => $realm->number,
                    'value' => $realm->{$stat},
                ];
                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['value'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['value'] = number_format($row['value']);
                return $row;
            });
    }

    protected function getHeroesByStatistic(Round $round, string $stat)
    {
        $builder = $round->dominions()
            ->human()
            ->with(['hero']);

        return $builder->get()
            ->filter(function (Dominion $dominion) {
                return isset($dominion->hero);
            })
            ->map(function (Dominion $dominion) use ($stat) {
                $player = '<a href="' . route('valhalla.user', $dominion->user->id) . '">' . htmlentities($dominion->user->display_name) . '</a>';

                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
                    'hero' => $dominion->hero->name,
                    'class' => ucwords($dominion->hero->class),
                    'player' => $player,
                    'race' => $dominion->race->name,
                    'realm' => $dominion->realm->number,
                    'value' => $dominion->hero->{$stat},
                ];
                return $data;
            })
            ->sortByDesc(function ($row) {
                return $row['value'];
            })
            ->take(100)
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['value'] = number_format($row['value']);
                return $row;
            });
    }
}
