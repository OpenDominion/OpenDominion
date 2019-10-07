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

            default:
                if (!preg_match('/(strongest|largest)-([-\w]+)/', $type, $matches)) {
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

        return view('pages.valhalla.round-type', compact(
            'round',
            'type',
            'headers',
            'data'
        ));
    }

    public function getUser(User $user)
    {
        // show valhalla of single user
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
                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
                    'player' => $dominion->user->display_name,
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
                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
                    'player' => $dominion->user->display_name,
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
}
