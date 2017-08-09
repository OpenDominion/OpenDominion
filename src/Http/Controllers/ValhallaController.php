<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Http\Response;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
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

        return view('pages.valhalla.round', [
            'round' => $round,
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
            'race' => ['width' => 100, 'align-center' => true],
            'realm' => ['width' => 100, 'align-center' => true],
            'number' => ['width' => 50, 'align-center' => true],
            'networth' => ['width' => 150, 'align-center' => true],
            'land' => ['width' => 150, 'align-center' => true],
        ];

        switch ($type) {
            case 'strongest-dominions': $data = $this->getStrongestDominions($round); break;
            case 'strongest-realms': $data = $this->getStrongestRealms($round); break;
            case 'largest-dominions': $data = $this->getLargestDominions($round); break;
            case 'largest-realms': $data = $this->getLargestRealms($round); break;

            default:
                if (!preg_match('/(strongest|largest)-(\w+)/', $type, $matches)) {
                    return redirect()->back()
                        ->withErrors(["Valhalla type '{$type}' not supported"]);
                }

                list(, $prefix, $raceName) = $matches;
                $raceName = ucfirst(str_singular($raceName)); // todo: refactor this

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

    protected function getStrongestDominions(Round $round, Race $race = null)
    {
        $networthCalculator = app(NetworthCalculator::class);

        $builder = $round->dominions()
            ->with(['realm', 'race.units'])
            ->limit(100);

        if ($race !== null) {
            $builder = $builder->where('race_id', $race->id);
        }

        return $builder->get()
            ->map(function (Dominion $dominion) use ($networthCalculator, $race) {
                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
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
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['networth'] = number_format($row['networth']);
                return $row;
            });
    }

    protected function getStrongestRealms(Round $round)
    {
        $networthCalculator = app(NetworthCalculator::class);

        return $round->realms()->with(['dominions.race.units'])->limit(100)->get()
            ->map(function (Realm $realm) use ($networthCalculator) {
                return [
                    '#' => null,
                    'realm name' => $realm->name,
                    'number' => $realm->number,
                    'networth' => $networthCalculator->getRealmNetworth($realm),
                ];
            })
            ->sortByDesc(function ($row) {
                return $row['networth'];
            })
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['networth'] = number_format($row['networth']);
                return $row;
            });
    }

    protected function getLargestDominions(Round $round, Race $race = null)
    {
        $landCalculator = app(LandCalculator::class);

        $builder = $round->dominions()
            ->with(['realm', 'race.units'])
            ->limit(100);

        if ($race !== null) {
            $builder = $builder->where('race_id', $race->id);
        }

        return $builder->get()
            ->map(function (Dominion $dominion) use ($landCalculator, $race) {
                $data = [
                    '#' => null,
                    'dominion' => $dominion->name,
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
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['land'] = number_format($row['land']);
                return $row;
            });
    }

    protected function getLargestRealms(Round $round)
    {
        $landCalculator = app(LandCalculator::class);

        return $round->realms()->with(['dominions.race.units'])->limit(100)->get()
            ->map(function (Realm $realm) use ($landCalculator) {
                return [
                    '#' => null,
                    'realm name' => $realm->name,
                    'number' => $realm->number,
                    'land' => $realm->dominions->reduce(function ($carry, Dominion $dominion) use ($landCalculator) {
                        return ($carry + $landCalculator->getTotalLand($dominion));
                    }),
                ];
            })
            ->sortByDesc(function ($row) {
                return $row['land'];
            })
            ->values()
            ->map(function ($row, $key) {
                $row['#'] = ($key + 1);
                $row['land'] = number_format($row['land']);
                return $row;
            });
    }
}
