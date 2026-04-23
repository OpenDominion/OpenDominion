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
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\UserOrigin;

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

        $crosslogs = UserOrigin::select('ip_address')
            ->selectRaw('GROUP_CONCAT(DISTINCT dominions.name ORDER BY dominions.name SEPARATOR ", ") AS dominions')
            ->selectRaw('GROUP_CONCAT(DISTINCT users.display_name ORDER BY users.display_name SEPARATOR ", ") AS users')
            ->selectRaw('GROUP_CONCAT(DISTINCT realms.number ORDER BY realms.number SEPARATOR ", ") AS realms')
            ->selectRaw('SUM(user_origins.count) AS count')
            ->join('dominions', 'dominions.id', '=', 'user_origins.dominion_id')
            ->join('realms', 'realms.id', '=', 'dominions.realm_id')
            ->join('users', 'users.id', '=', 'dominions.user_id')
            ->whereIn('user_origins.dominion_id', $dominionIds)
            ->where('user_origins.ip_address', '!=', '127.0.0.1')
            ->groupBy('user_origins.ip_address')
            ->havingRaw('COUNT(DISTINCT user_origins.dominion_id) > 1')
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

        $realms = Realm::where('round_id', $round->id)->pluck('number', 'id');

        $invasions = GameEvent::selectRaw('game_events.*, source.name AS source_name, source.realm_id AS source_realm_id, target.name AS target_name, target.realm_id AS target_realm_id, COUNT(info_ops.source_realm_id) AS ops_count')
            ->join('dominions AS source', 'game_events.source_id', '=', 'source.id')
            ->join('dominions AS target', 'game_events.target_id', '=', 'target.id')
            ->leftJoin('info_ops', function (JoinClause $join) {
                $join->on('info_ops.target_dominion_id', '=', 'target.id')
                    ->on('info_ops.source_realm_id', '=', 'source.realm_id')
                    ->where('info_ops.created_at', '<', DB::raw('game_events.created_at'))
                    ->where('info_ops.created_at', '>', DB::raw('DATE_SUB(game_events.created_at, INTERVAL 12 HOUR)'));
            })
            ->where('game_events.round_id', $round->id)
            ->where('game_events.type', 'invasion')
            ->groupBy('game_events.id')
            ->having('ops_count', '<', 3)
            ->orderByDesc('game_events.created_at')
            ->get();

        return view('pages.staff.administrator.dominions.invasions', [
            'round' => $round,
            'rounds' => $rounds,
            'invasions' => $invasions,
            'realms' => $realms,
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

        $theft = DB::table('dominion_history')
            ->selectRaw('dominion_history.dominion_id, JSON_EXTRACT(delta, "$.target_dominion_id") AS target_dominion_id, source.name AS source_name, target.name AS target_name, COUNT(*) AS count')
            ->join('dominions AS source', 'source.id', '=', 'dominion_history.dominion_id')
            ->join('dominions AS target', 'target.id', '=', DB::raw('JSON_EXTRACT(delta, "$.target_dominion_id")'))
            ->whereIn('dominion_history.dominion_id', $dominionIds)
            ->where('event', 'perform espionage operation')
            ->where('delta', 'like', '%steal_platinum%')
            ->whereRaw('JSON_EXTRACT(delta, "$.target_dominion_id") IS NOT NULL')
            ->groupBy('dominion_history.dominion_id', 'target_dominion_id')
            ->havingRaw('COUNT(*) > 2')
            ->orderByDesc('count')
            ->get();

        return view('pages.staff.administrator.dominions.theft', [
            'round' => $round,
            'rounds' => $rounds,
            'theft' => $theft,
        ]);
    }

    public function getRepeatInvasions(Request $request)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        $leagueRoundIds = Round::where('round_league_id', $round->round_league_id)->pluck('id');

        // Find attacker->defender user pairs with successful invasions in the current round
        $currentRoundPairs = DB::table('game_events')
            ->select('source.user_id AS source_user_id', 'target.user_id AS target_user_id')
            ->join('dominions AS source', 'source.id', '=', 'game_events.source_id')
            ->join('dominions AS target', 'target.id', '=', 'game_events.target_id')
            ->where('game_events.round_id', $round->id)
            ->where('game_events.type', 'invasion')
            ->whereRaw('JSON_EXTRACT(game_events.data, "$.result.success") = true')
            ->distinct()
            ->get();

        if ($currentRoundPairs->isEmpty()) {
            $repeatInvasions = collect();
        } else {
            // Get cross-league totals for those pairs
            $repeatInvasions = DB::table('game_events')
                ->selectRaw('source_user.id AS source_user_id, source_user.display_name AS source_user_name, target_user.id AS target_user_id, target_user.display_name AS target_user_name, COUNT(*) AS total_invasions, COUNT(DISTINCT game_events.round_id) AS rounds')
                ->join('dominions AS source', 'source.id', '=', 'game_events.source_id')
                ->join('dominions AS target', 'target.id', '=', 'game_events.target_id')
                ->join('users AS source_user', 'source_user.id', '=', 'source.user_id')
                ->join('users AS target_user', 'target_user.id', '=', 'target.user_id')
                ->whereIn('game_events.round_id', $leagueRoundIds)
                ->where('game_events.type', 'invasion')
                ->whereRaw('JSON_EXTRACT(game_events.data, "$.result.success") = true')
                ->where(function ($query) use ($currentRoundPairs) {
                    foreach ($currentRoundPairs as $pair) {
                        $query->orWhere(function ($q) use ($pair) {
                            $q->where('source.user_id', $pair->source_user_id)
                                ->where('target.user_id', $pair->target_user_id);
                        });
                    }
                })
                ->groupBy('source_user.id', 'target_user.id')
                ->havingRaw('rounds > 1')
                ->orderByDesc('rounds')
                ->orderByDesc('total_invasions')
                ->get();
        }

        return view('pages.staff.administrator.dominions.repeat-invasions', [
            'round' => $round,
            'rounds' => $rounds,
            'repeatInvasions' => $repeatInvasions,
        ]);
    }
}
