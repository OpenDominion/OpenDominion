<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use Carbon\Carbon;
use DateInterval;
use DB;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Round;

class DominionController extends AbstractController
{
    public function index(Request $request)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        $dominions = Dominion::with([
            'queues',
            'race',
            'race.perks',
            'race.units',
            'race.units.perks',
            'techs',
            'techs.perks',
            'user'
        ])->where('round_id', $round->id)->get();

        return view('pages.staff.administrator.dominions.index', [
            'round' => $round,
            'rounds' => $rounds,
            'dominions' => $dominions,
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
        ]);
    }

    public function show(Dominion $dominion)
    {
        return view('pages.staff.administrator.dominions.show', [
            'dominion' => $dominion,
        ]);
    }

    public function getCrosslogs(Request $request)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        $dominionIds = $round->dominions()->where('user_id', '!=', null)->pluck('dominions.id');

        $crosslogs = DB::table('dominion_history')
            ->selectRaw('COUNT(*) AS count, ip, GROUP_CONCAT(DISTINCT dominions.name SEPARATOR ", ") AS dominions, GROUP_CONCAT(DISTINCT realms.number SEPARATOR ", ") AS realms, GROUP_CONCAT(DISTINCT users.display_name SEPARATOR ", ") AS users')
            ->join('dominions', 'dominions.id', '=', 'dominion_history.dominion_id')
            ->join('realms', 'realms.id', '=', 'dominions.realm_id')
            ->join('users', 'users.id', '=', 'dominions.user_id')
            ->whereIn('dominion_id', $dominionIds)
            ->where('ip', '!=', '127.0.0.1')
            ->whereIn('event', [
                'train',
                'construct',
                'explore',
                'invest',
                'perform espionage operation',
                'cast spell'
            ])
            ->groupBy('ip')
            ->havingRaw('COUNT(DISTINCT dominion_id) > 1')
            ->get();

        return view('pages.staff.administrator.dominions.crosslogs', [
            'round' => $round,
            'rounds' => $rounds,
            'crosslogs' => $crosslogs,
        ]);
    }

    public function getInvasions(Request $request)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        $invasions = GameEvent::selectRaw('game_events.*, source.name AS source_name, target.name AS target_name, COUNT(info_ops.source_realm_id) AS ops_count')
            ->join('dominions AS source', 'game_events.source_id', '=', 'source.id')
            ->join('dominions AS target', 'game_events.target_id', '=', 'target.id')
            ->leftJoin('info_ops', function (JoinClause $join) {
                $join->on('target.id', '=', 'info_ops.target_dominion_id')
                    ->where('info_ops.created_at', '<', DB::raw('game_events.created_at'))
                    ->where('info_ops.created_at', '>', DB::raw('DATE_SUB(game_events.created_at, INTERVAL 12 HOUR)'));
            })
            ->where('game_events.round_id', $round->id)
            ->groupBy('game_events.id')
            ->having('ops_count', '<', 3)
            ->orderByDesc('game_events.created_at')
            ->get();

        return view('pages.staff.administrator.dominions.invasions', [
            'round' => $round,
            'rounds' => $rounds,
            'invasions' => $invasions,
        ]);
    }

    public function getTheft(Request $request)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        $dominionIds = $round->dominions()->where('user_id', '!=', null)->pluck('dominions.id');
        $dominionNames = $round->dominions()->pluck('dominions.name', 'dominions.id');

        $theftActions = DB::table('dominion_history')
            ->whereIn('dominion_id', $dominionIds)
            ->where('event', 'perform espionage operation')
            ->where('delta', 'like', '%steal_platinum%')
            ->get();

        $theft = [];
        foreach ($theftActions as $action) {
            $data = json_decode($action->delta);
            if (isset($data->target_dominion_id)) {
                $target_id = $data->target_dominion_id;
                if (!isset($theft[$action->dominion_id][$target_id])) {
                    array_set($theft, "{$action->dominion_id}.{$target_id}", 0);
                }
                $theft[$action->dominion_id][$target_id]++;
            }
        }

        return view('pages.staff.administrator.dominions.theft', [
            'round' => $round,
            'rounds' => $rounds,
            'theft' => $theft,
            'dominionNames' => $dominionNames,
        ]);
    }
}
