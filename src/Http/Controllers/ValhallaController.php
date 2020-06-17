<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Http\Response;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

class ValhallaController extends AbstractController
{
    public function getIndex()
    {
        $rounds = Round::with('league')->orderBy('start_date', 'desc')->get();

        return view('pages.valhalla.index', [
            'rounds' => $rounds,
        ]);
    }

    public function getRound(Round $round)
    {
        if ($response = $this->guardAgainstActiveRound($round)) {
            return $response;
        }

        $races = $round->dominions
            ->sortBy('race.name')
            ->pluck('race.name', 'race.id')
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
            case 'strongest-dominions': $data = $this->getStrongestDominions($round); break;
            case 'strongest-good-dominions': $data = $this->getStrongestDominions($round, null, 'good'); break;
            case 'strongest-evil-dominions': $data = $this->getStrongestDominions($round, null, 'evil'); break;
            case 'strongest-realms': $data = $this->getStrongestRealms($round); break;
            case 'strongest-good-realms': $data = $this->getStrongestRealms($round, 'good'); break;
            case 'strongest-evil-realms': $data = $this->getStrongestRealms($round, 'evil'); break;
            case 'strongest-packs': $data = $this->getStrongestPacks($round); break;
            case 'largest-dominions': $data = $this->getLargestDominions($round); break;
            case 'largest-good-dominions': $data = $this->getLargestDominions($round, null, 'good'); break;
            case 'largest-evil-dominions': $data = $this->getLargestDominions($round, null, 'evil'); break;
            case 'largest-realms': $data = $this->getLargestRealms($round); break;
            case 'largest-good-realms': $data = $this->getLargestRealms($round, 'good'); break;
            case 'largest-evil-realms': $data = $this->getLargestRealms($round, 'evil'); break;
            case 'largest-packs': $data = $this->getLargestPacks($round); break;
            case 'stat-prestige': $data = $this->getDominionsByStatistic($round, 'prestige'); break;
            case 'stat-attacking-success': $data = $this->getDominionsByStatistic($round, 'stat_attacking_success'); break;
            case 'stat-defending-success': $data = $this->getDominionsByStatistic($round, 'stat_defending_success'); break;
            case 'stat-espionage-success': $data = $this->getDominionsByStatistic($round, 'stat_espionage_success'); break;
            case 'stat-spell-success': $data = $this->getDominionsByStatistic($round, 'stat_spell_success'); break;
            case 'stat-spy-prestige': $data = $this->getDominionsByStatistic($round, 'stat_spy_prestige'); break;
            case 'stat-wizard-prestige': $data = $this->getDominionsByStatistic($round, 'stat_wizard_prestige'); break;
            case 'stat-spies-executed': $data = $this->getDominionsByStatistic($round, 'stat_spies_executed'); break;
            case 'stat-wizards-executed': $data = $this->getDominionsByStatistic($round, 'stat_wizards_executed'); break;
            //case 'stat-total-platinum-production': $data = $this->getDominionsByStatistic($round, 'stat_total_platinum_production'); break;
            //case 'stat-total-food-production': $data = $this->getDominionsByStatistic($round, 'stat_total_food_production'); break;
            //case 'stat-total-lumber-production': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_production'); break;
            //case 'stat-total-mana-production': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_production'); break;
            //case 'stat-total-ore-production': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_production'); break;
            //case 'stat-total-gem-production': $data = $this->getDominionsByStatistic($round, 'stat_total_gem_production'); break;
            //case 'stat-total-tech-production': $data = $this->getDominionsByStatistic($round, 'stat_total_tech_production'); break;
            //case 'stat-total-boat-production': $data = $this->getDominionsByStatistic($round, 'stat_total_boat_production'); break;
            case 'stat-total-land-explored': $data = $this->getDominionsByStatistic($round, 'stat_total_land_explored'); break;
            case 'stat-total-land-conquered': $data = $this->getDominionsByStatistic($round, 'stat_total_land_conquered'); break;
            case 'stat-total-platinum-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_platinum_stolen'); break;
            case 'stat-total-food-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_food_stolen'); break;
            case 'stat-total-lumber-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_lumber_stolen'); break;
            case 'stat-total-mana-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_mana_stolen'); break;
            case 'stat-total-ore-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_ore_stolen'); break;
            case 'stat-total-gems-stolen': $data = $this->getDominionsByStatistic($round, 'stat_total_gems_stolen'); break;
            case 'stat-top-saboteurs': $data = $this->getDominionsByStatistic($round, 'stat_sabotage_boats_damage'); break;
            case 'stat-top-magical-assassins': $data = $this->getDominionsByStatistic($round, 'stat_assassinate_wizards_damage'); break;
            case 'stat-top-military-assassins': $data = $this->getDominionsByStatistic($round, 'stat_assassinate_draftees_damage'); break;
            case 'stat-top-snare-setters': $data = $this->getDominionsByStatistic($round, 'stat_magic_snare_damage'); break;
            case 'stat-masters-of-fire': $data = $this->getDominionsByStatistic($round, 'stat_fireball_damage'); break;
            case 'stat-masters-of-plague': $data = $this->getDominionsByStatistic($round, 'stat_plague_hours'); break;
            case 'stat-masters-of-swarm': $data = $this->getDominionsByStatistic($round, 'stat_insect_swarm_hours'); break;
            case 'stat-masters-of-lightning': $data = $this->getDominionsByStatistic($round, 'stat_lightning_bolt_damage'); break;
            case 'stat-masters-of-water': $data = $this->getDominionsByStatistic($round, 'stat_great_flood_hours'); break;
            case 'stat-masters-of-earth': $data = $this->getDominionsByStatistic($round, 'stat_earthquake_hours'); break;
            case 'stat-top-spy-disbanders': $data = $this->getDominionsByStatistic($round, 'stat_disband_spies_damage'); break;
            case 'realm-stat-prestige': $data = $this->getRealmsByStatistic($round, 'prestige'); break;
            case 'realm-stat-attacking-success': $data = $this->getRealmsByStatistic($round, 'stat_attacking_success'); break;
            case 'stat-defending-success': $data = $this->getRealmsByStatistic($round, 'stat_defending_success'); break;
            case 'stat-defending-failure': $data = $this->getRealmsByStatistic($round, 'stat_defending_failure'); break;
            case 'realm-stat-total-land-explored': $data = $this->getRealmsByStatistic($round, 'stat_total_land_explored'); break;
            case 'realm-stat-total-land-conquered': $data = $this->getRealmsByStatistic($round, 'stat_total_land_conquered'); break;

            default:
                if (!preg_match('/(strongest|largest|stat)-([-\w]+)/', $type, $matches)) {
                    return redirect()->back()
                        ->withErrors(["Valhalla type '{$type}' not supported"]);
                }

                list(, $prefix, $raceName) = $matches;
                $raceName = ucwords(str_replace('-', ' ', $raceName));

                $race = Race::where('name', $raceName)->firstOrFail();

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

        $dominions = $user->dominions()
            ->with(['queues', 'realm', 'race.units.perks'])
            ->orderByDesc('round_id')
            ->get()
            ->filter(function (Dominion $dominion) {
                if ($dominion->round->end_date < now()) return $dominion;
            });

        return view('pages.valhalla.user', [
            'player' => $user,
            'dominions' => $dominions,
            'landCalculator' => $landCalculator,
            'networthCalculator' => $networthCalculator,
        ]);
    }

    // todo: search user

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
            ->with(['dominions.user', 'realm', 'user']);

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
            ->with(['dominions.user', 'realm', 'user']);

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
            ->with(['dominions']);

        return $builder->get()
            ->map(function ($realm) use ($stat) {
                $realm->{$stat} = $realm->dominions->sum($stat);
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
}
