<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Dominion;

class BattleResultController
{
    public function index()
    {
        // hacky assoc-array-to-object via json fuckery
        $battleResult = json_decode(json_encode([
            'result' => [
                'success' => true, // invasion successful or not
                'outmatchedCasualtiesMultiplier' => 0.15, // not exists if success==true
            ],
            'attacker' => [
                'dominionId' => 1,
                'prestigeChange' => 25,
                'unitsLost' => [
                    1 => 10,
                    3 => 5,
                    4 => 5,
                ],
                'landConquered' => [
                    'plain' => 5,
                    'mountain' => 2,
                    'swamp' => 2,
                    'cavern' => 5,
                    'forest' => 2,
                    'hill' => 2,
                    'water' => 0,
                ],
                'converts' => [ // optional section
                    'unit1' => 10,
                ],
                'heroes' => [ // stuff for later, but felt like writing it down
                    'leveled' => [1, 2, 3], // hero ids
                    'created' => [4], // new heroes!
                ],
            ],
            'defender' => [
                'dominionId' => 2,
                'prestigeChange' => -25,
                'recentlyInvadedLevel' => 3, // invaded 3 times in the last 24 hrs
                'unitsLost' => [
                    'draftees' => 5000,
                    2 => 3,
                    3 => 2,
                    4 => 2,
                ],
            ],
        ]));

        return view('pages.dominion.battle-result', [
            'battleResult' => $battleResult,
            'attackerDominion' => Dominion::query()
                ->with('race.units', 'realm')
                ->findOrFail($battleResult->attacker->dominionId),
            'defenderDominion' => Dominion::query()
                ->with('race.units', 'realm')
                ->findOrFail($battleResult->defender->dominionId),
        ]);
    }
}
