<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Http\Response;
use OpenDominion\Contracts\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
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

        $networthCalculator = app(NetworthCalculator::class);

        $headers = [
            '#' => ['width' => 50, 'align-center' => true],
            'race' => ['width' => 100, 'align-center' => true],
            'realm' => ['width' => 100, 'align-center' => true],
            'networth' => ['width' => 150, 'align-center' => true],
            'land' => ['width' => 150, 'align-center' => true],
        ];

        // todo: refactor
        switch ($type) {
            case 'strongest-dominions':
                $data = $round->dominions()->limit(100)->get()
                    ->map(function (Dominion $dominion) use ($networthCalculator) {
                        return [
                            '#' => null,
                            'dominion' => $dominion->name,
                            'race' => $dominion->race->name,
                            'realm' => $dominion->realm->number,
                            'networth' => $networthCalculator->getDominionNetworth($dominion),
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
                break;

            default:
                return redirect()->back()
                    ->withErrors(["Valhalla type '{$type}' not supported"]);
        }

        return view('pages.valhalla.round-type', [ // todo: compact()
            'round' => $round,
            'type' => $type,
            'headers' => $headers,
            'data' => $data,
        ]);
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
        if ($round->isActive()) {
            return redirect()->back()
                ->withErrors(['Active rounds cannot be viewed in Valhalla']);
        }

        return null;
    }
}
