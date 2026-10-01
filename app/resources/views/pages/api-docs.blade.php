@extends('layouts.topnav')

@section('title', 'API Documentation')

@section('content')
    <div class="row">
        <div class="col-lg-10 offset-lg-1">

            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-book"></i> API Documentation</span>
                </div>
                <div class="card-body">
                    <p>
                        OpenDominion has a read-only JSON API. Base URL: <code>{{ url('/api/v1') }}</code>
                    </p>

                    <h5 class="fw-bold" id="authentication">Authentication</h5>
                    <p>
                        Endpoints under <code>/rounds</code> are public and need no key. Endpoints under
                        <code>/dominions</code> require a dominion API key, sent as an <code>X-API-Key</code> header
                        or as <code>Authorization: Bearer &lt;key&gt;</code>.
                    </p>
                    <p>
                        A key belongs to one dominion in one round. Generate it from that dominion's Settings page
                        while playing. It keeps working after the round ends.
                    </p>
<pre class="bg-body-tertiary border rounded p-2"><code>curl -H "X-API-Key: YOUR_KEY" {{ route('api.dominions.me') }}</code></pre>

                    <h5 class="fw-bold" id="rate-limits">Rate limits</h5>
                    <p>
                        60 requests per minute per IP address, shared across all endpoints. Exceeding the limit
                        returns <code>429</code> with a <code>Retry-After</code> header.
                    </p>

                    <h5 class="fw-bold" id="timestamps">Timestamps</h5>
                    <p>
                        All timestamps are UTC in ISO 8601 format with a <code>Z</code> suffix, e.g.
                        <code>2026-09-30T11:45:00Z</code>.
                    </p>

                    <h5 class="fw-bold" id="errors">Errors</h5>
                    <p class="mb-1">Errors are returned as <code>{"error": "code", "message": "..."}</code>.</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Error</th>
                                    <th>Meaning</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>401</td>
                                    <td><code>missing_api_key</code></td>
                                    <td>No key was sent.</td>
                                </tr>
                                <tr>
                                    <td>401</td>
                                    <td><code>invalid_api_key</code></td>
                                    <td>The key was not recognised (it may have been regenerated or revoked). Keys are revoked automatically when a dominion is abandoned.</td>
                                </tr>
                                <tr>
                                    <td>403</td>
                                    <td><code>dominion_locked</code></td>
                                    <td>The dominion is locked or abandoned.</td>
                                </tr>
                                <tr>
                                    <td>403</td>
                                    <td><code>under_protection</code></td>
                                    <td>Your dominion is in protection. The op center endpoints are unavailable until protection ends; <code>/dominions/me</code> and <code>/dominions/me/advisors</code> are still available.</td>
                                </tr>
                                <tr>
                                    <td>403</td>
                                    <td><code>round_not_started</code></td>
                                    <td>
                                        The round has not started yet. Until it does, only <code>/rounds</code>,
                                        <code>/dominions/me</code> and <code>/dominions/me/advisors</code> are available.
                                    </td>
                                </tr>
                                <tr>
                                    <td>404</td>
                                    <td><code>not_found</code></td>
                                    <td>
                                        No round or dominion exists with the ID in the URL, the dominion is not in
                                        your round, or your realm has no info ops on it.
                                    </td>
                                </tr>
                                <tr>
                                    <td>422</td>
                                    <td><code>invalid_parameter</code></td>
                                    <td>
                                        A parameter is invalid: an unparseable <code>since</code>, an unknown event
                                        <code>type</code>, an unknown op type in the Op Archive URL, or a parameter sent
                                        as an array (e.g. <code>?type[]=invasion</code>).
                                    </td>
                                </tr>
                                <tr>
                                    <td>422</td>
                                    <td><code>same_realm</code></td>
                                    <td>An op center endpoint was requested for a dominion in your own realm. Use <code>/dominions/me/advisors</code> for their current data.</td>
                                </tr>
                                <tr>
                                    <td>429</td>
                                    <td><code>rate_limited</code></td>
                                    <td>Rate limit exceeded; see the <code>Retry-After</code> header.</td>
                                </tr>
                                <tr>
                                    <td>500</td>
                                    <td><code>server_error</code></td>
                                    <td>An unexpected error on our side.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Round endpoints</span>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-1" id="rounds">Rounds</h5>
                    <p class="mb-2"><span class="badge text-bg-success">GET</span> <code>/rounds</code></p>
                    <p>All rounds, newest first.</p>
<pre class="bg-body-tertiary border rounded p-2"><code>[
    {
        "id": 51,
        "number": 51,
        "name": "Round 51",
        "description": null,
        "league": {"id": 1, "key": "standard", "description": "Standard"},
        "start_date": "2026-09-01T00:00:00Z",
        "end_date": "2026-10-18T00:00:00Z",
        "has_started": true,
        "has_ended": false
    }
]</code></pre>

                    <h5 class="fw-bold mb-1" id="round-dominions">Search</h5>
                    <p class="mb-2"><span class="badge text-bg-success">GET</span> <code>/rounds/{round}/dominions</code></p>
                    <p class="mb-1">
                        Every dominion in a round, including locked and abandoned ones.
                    </p>
                    <ul>
                        <li>
                            <code>guard</code> is <code>"royal"</code>, <code>"elite"</code> or <code>null</code>.
                            Black Guard membership is not included.
                        </li>
                        <li>
                            <code>locked</code> is <code>true</code> when the dominion has been locked by an
                            administrator. <code>abandoned</code> is <code>true</code> once an abandonment has taken
                            effect; a pending abandonment is still <code>false</code>.
                        </li>
                        <li>
                            <code>shares_advisors</code> is only included when you send an API key for a dominion in
                            this round. It is <code>true</code> for your own dominion and for realmies whose advisors
                            you can view in <a href="#dominions-me-advisors">Realm Advisors</a>, and <code>false</code> for
                            everyone else. The API key is optional here; an invalid one still returns
                            <code>401</code>.
                        </li>
                    </ul>
<pre class="bg-body-tertiary border rounded p-2"><code>[
    {
        "id": 5678,
        "name": "Target Dominion",
        "race": "Nomad",
        "realm_number": 12,
        "realm_name": "Their Realm",
        "land": 250,
        "networth": 1500,
        "in_protection": false,
        "guard": "royal",
        "locked": false,
        "abandoned": false,
        "shares_advisors": false
    }
]</code></pre>

                    <h5 class="fw-bold mb-1" id="round-realms">Realms</h5>
                    <p class="mb-2"><span class="badge text-bg-success">GET</span> <code>/rounds/{round}/realms</code></p>
                    <p class="mb-1">Every realm in a round, with the wonders it holds and its current wars.</p>
                    <ul>
                        <li>
                            <code>wonders</code> lists the wonders the realm holds right now. <code>power</code> is
                            rounded (<code>power_is_approximate</code> is <code>true</code>) unless you send an API key
                            for the holding realm or a realm at war with it, as on the in-game wonders page. The API
                            key is optional here; an invalid one still returns <code>401</code>.
                        </li>
                        <li>
                            <code>wars</code> lists wars that have not ended, the same ones shown on the in-game realm
                            page. Each war appears under both realms: <code>direction</code> is
                            <code>"outgoing"</code> for the realm that declared it and <code>"incoming"</code> for
                            the other, and <code>realm_number</code> / <code>realm_name</code> are the other realm.
                        </li>
                        <li>
                            <code>status</code> is <code>"pending"</code> (declared, not active until
                            <code>active_at</code>), <code>"active"</code>, or <code>"expiring"</code> (canceled,
                            still active until <code>inactive_at</code>).
                        </li>
                    </ul>
<pre class="bg-body-tertiary border rounded p-2"><code>[
    {
        "number": 7,
        "name": "Defenders",
        "wonders": [
            {
                "key": "high_clerics_tower",
                "name": "High Cleric's Tower",
                "power": 230000,
                "max_power": 250000,
                "power_is_approximate": true
            }
        ],
        "wars": [
            {
                "direction": "incoming",
                "realm_number": 3,
                "realm_name": "Aggressors",
                "status": "active",
                "declared_at": "2026-09-29T10:00:00Z",
                "active_at": "2026-09-30T10:00:00Z",
                "inactive_at": null
            }
        ]
    }
]</code></pre>

                    <h5 class="fw-bold mb-1" id="round-events">Town Crier</h5>
                    <p class="mb-2"><span class="badge text-bg-success">GET</span> <code>/rounds/{round}/events</code></p>
                    <p class="mb-1">A round's Town Crier events, newest first.</p>
                    <ul>
                        <li><code>limit</code> (optional, default 100, maximum 500): number of events to return.</li>
                        <li>
                            <code>since</code> (optional): ISO 8601 timestamp; only events created at or after it are
                            returned. An unparseable value returns <code>422</code>.
                        </li>
                        <li>
                            <code>type</code> (optional): only return events of this type. Accepts one type or a
                            comma-separated list, e.g. <code>?type=war_declared,war_canceled</code>. A type not
                            listed below returns <code>422</code>.
                        </li>
                        <li>
                            When the source or target is a dominion or a realm, <code>source_name</code> /
                            <code>target_name</code> and <code>source_realm_number</code> /
                            <code>target_realm_number</code> give its name and realm number, including for dominions
                            that have since been abandoned or locked. For any other kind of source or target they are
                            <code>null</code>, and the table below says where its details are.
                        </li>
                        <li>
                            The possible values of <code>type</code> are listed in the table below, along with what
                            the source and target are and what <code>data</code> holds for each. <code>data</code>
                            carries what the in-game Town Crier shows for that event; battle reports and other
                            details are private and are not returned.
                        </li>
                    </ul>
<pre class="bg-body-tertiary border rounded p-2"><code>[
    {
        "id": "9d2f6c1e-4b7a-4e0c-8f3a-2b1c5d6e7f80",
        "type": "invasion",
        "source_type": "dominion",
        "source_id": 1234,
        "source_name": "Attacker",
        "source_realm_number": 3,
        "target_type": "dominion",
        "target_id": 5678,
        "target_name": "Target Dominion",
        "target_realm_number": 12,
        "data": {"success": true, "land_lost": 15, "land_gained": 19},
        "created_at": "2026-09-30T11:45:00Z"
    }
]</code></pre>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Source</th>
                                    <th>Target</th>
                                    <th><code>data</code></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>invasion</code></td>
                                    <td>Attacking dominion</td>
                                    <td>Defending dominion</td>
                                    <td>
                                        <code>{"success": true, "land_lost": 15, "land_gained": 19}</code><br>
                                        <code>land_lost</code> is the land taken from the defender.
                                        <code>land_gained</code> is what the attacker will receive. Both are
                                        <code>0</code> when the invasion failed.
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>war_declared</code><br><code>war_canceled</code></td>
                                    <td>Declaring realm</td>
                                    <td>Realm war</td>
                                    <td>
                                        <code>{"source_realm": {"number": 3, "name": "..."}, "target_realm": {"number": 7, "name": "..."}}</code>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>wonder_spawned</code></td>
                                    <td>Wonder</td>
                                    <td>Wonder</td>
                                    <td><code>{"wonder": "Ivory Tower"}</code></td>
                                </tr>
                                <tr>
                                    <td><code>wonder_attacked</code></td>
                                    <td>Attacking dominion</td>
                                    <td>Round wonder</td>
                                    <td>
                                        <code>{"neutral": false, "wonder": "Ivory Tower", "realm_number": 7}</code><br>
                                        When a neutral wonder is attacked, <code>neutral</code> is <code>true</code>
                                        and <code>wonder</code>, <code>realm_number</code> and the event's
                                        <code>target_id</code> are <code>null</code>.
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>wonder_destroyed</code></td>
                                    <td>Round wonder</td>
                                    <td>Realm that rebuilt it, if any</td>
                                    <td>
                                        <code>{"wonder": "Ivory Tower", "rebuilt_by_realm": {"number": 7, "name": "..."}}</code><br>
                                        <code>rebuilt_by_realm</code> is <code>null</code> when the wonder was not
                                        rebuilt.
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>raid_attacked</code></td>
                                    <td>Attacking dominion</td>
                                    <td>Raid tactic</td>
                                    <td><code>{"tactic": "Storm the Gates"}</code></td>
                                </tr>
                                <tr>
                                    <td><code>abandoned</code></td>
                                    <td>Abandoned dominion</td>
                                    <td><code>null</code></td>
                                    <td><code>{}</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-key"></i> Dominion endpoints (API key required)</span>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-1" id="dominions-me">My Dominion</h5>
                    <p class="mb-2"><span class="badge text-bg-success">GET</span> <code>/dominions/me</code></p>
                    <p>
                        The dominion the key belongs to, with its realm and round. <code>round.day</code> and
                        <code>round.hour</code> are <code>null</code> before the round starts. Current stats for your
                        dominion are in <a href="#dominions-me-advisors">Realm Advisors</a>.
                    </p>
<pre class="bg-body-tertiary border rounded p-2"><code>{
    "id": 1234,
    "name": "My Dominion",
    "realm": {"id": 56, "number": 7, "name": "My Realm"},
    "round": {
        "id": 51,
        "number": 51,
        "name": "Round 51",
        "start_date": "2026-09-01T00:00:00Z",
        "end_date": "2026-10-18T00:00:00Z",
        "day": 30,
        "hour": 12,
        "duration_days": 47
    },
    "server_time": "2026-09-30T11:14:08Z",
    "links": {
        "advisors": "{{ url('/api/v1/dominions/me/advisors') }}",
        "op_center": "{{ url('/api/v1/dominions/me/op-center') }}",
        "rounds": "{{ url('/api/v1/rounds') }}",
        "round_dominions": "{{ url('/api/v1/rounds/51/dominions') }}",
        "round_events": "{{ url('/api/v1/rounds/51/events') }}",
        "round_realms": "{{ url('/api/v1/rounds/51/realms') }}"
    }
}</code></pre>

                    <h5 class="fw-bold mb-1" id="dominions-me-advisors">Realm Advisors</h5>
                    <p class="mb-2"><span class="badge text-bg-success">GET</span> <code>/dominions/me/advisors</code></p>
                    <p class="mb-1">
                        Current data for your own dominion and every realmie who shares their advisors with you, as
                        on the in-game realm advisors pages, keyed by dominion ID with your own dominion first.
                        Available while you are in protection and before the round starts.
                    </p>
                    <ul>
                        <li>
                            Realmies who do not share their advisors with you are left out. Dominions that join
                            after realm assignment do not see realmies' advisors unless a realmie shares with them
                            explicitly.
                        </li>
                        <li>
                            <code>ops</code> has the same eight keys and fields as the
                            <a href="#dominions-me-op-center">Op Center</a>, built from the dominion's current state
                            instead of info ops: every type is filled in, each with <code>created_at</code> equal to
                            <code>generated_at</code>. The example below shortens it.
                        </li>
                        <li>
                            <code>military</code> includes units returning from invasion but not units in training;
                            strengths and modifiers are percentages (<code>23.5</code> means +23.5%).
                        </li>
                        <li>
                            <code>statistics</code> are totals for this round; each <code>{resource}_spent</code> is
                            the sum of its <code>{resource}_spent_{category}</code> breakdown.
                        </li>
                    </ul>
<pre class="bg-body-tertiary border rounded p-2"><code>{
    "generated_at": "2026-09-30T12:00:00Z",
    "realm": {"id": 56, "number": 7, "name": "My Realm"},
    "dominions": {
        "1234": {
            "id": 1234,
            "name": "My Dominion",
            "race": "Human",
            "ops": {
                "clear_sight": {"name": "My Dominion", "land": 2950, "created_at": "2026-09-30T12:00:00Z"},
                "revelation": {"spells": [], "created_at": "2026-09-30T12:00:00Z"},
                ...
            },
            "resources": {
                "platinum": 523000, "food": 180000, "lumber": 41000, "mana": 92000,
                "ore": 60000, "gems": 15000, "tech": 3400, "boats": 112.5
            },
            "military": {
                "draftees": 2500, "unit1": 0, "unit2": 8000, "unit3": 3200, "unit4": 2100,
                "spies": 900, "assassins": 300, "wizards": 1400, "archmages": 120,
                "spy_strength": 100, "wizard_strength": 87.5,
                "offensive_modifier": 23.5, "defensive_modifier": 17.25,
                "spy_ratio": {"offense": 0.612, "defense": 0.585},
                "wizard_ratio": {"offense": 0.934, "defense": 0.901}
            },
            "land": {
                "plain": 400, "mountain": 350, "swamp": 300, "cavern": 250,
                "forest": 300, "hill": 450, "water": 450
            },
            "buildings": {
                "home": 250, "alchemy": 120, "farm": 90, "smithy": 60, "masonry": 100,
                "ore_mine": 80, "gryphon_nest": 150, "tower": 110, "wizard_guild": 40, "temple": 60,
                "diamond_mine": 200, "school": 70, "lumberyard": 60, "factory": 30,
                "guard_tower": 150, "shrine": 20, "barracks": 250, "dock": 200
            },
            "hourly": {
                "production": {
                    "platinum": 18500, "food": 6200, "lumber": 900, "mana": 3100,
                    "ore": 1500, "gems": 2400, "tech": 140, "boats": 1.25
                },
                "consumption": {"food": 5400},
                "decay": {"food": 180, "lumber": 410, "mana": 1840},
                "net_change": {"food": 620, "lumber": 490, "mana": 1260}
            },
            "population": {
                "total": 48000, "max": 52000, "peasants": 36000,
                "military": 12000, "jobs": 30000, "employed": 30000
            },
            "statistics": {
                "platinum_spent": 4200000,
                "platinum_spent_construction": 1500000, "platinum_spent_exploration": 900000,
                "platinum_spent_investment": 300000, "platinum_spent_rezoning": 50000,
                "platinum_spent_training": 1450000,
                "lumber_spent": 310000,
                "lumber_spent_construction": 260000, "lumber_spent_investment": 40000, "lumber_spent_training": 10000,
                "mana_spent": 95000, "mana_spent_investment": 60000, "mana_spent_training": 35000,
                "ore_spent": 520000, "ore_spent_investment": 120000, "ore_spent_training": 400000,
                "gems_spent": 180000, "gems_spent_investment": 180000, "gems_spent_training": 0
            }
        }
    }
}</code></pre>

                    <h5 class="fw-bold mb-1" id="dominions-me-op-center">Op Center</h5>
                    <p class="mb-2"><span class="badge text-bg-success">GET</span> <code>/dominions/me/op-center</code></p>
                    <p class="mb-1">
                        Your realm's Op Center: the latest info ops gathered by anyone in your realm on every
                        dominion they have targeted, keyed by that dominion's ID.
                    </p>
                    <ul>
                        <li>
                            <code>max_age_hours</code> (optional, default 12): only include ops gathered within this
                            many hours. Send <code>0</code> for no age limit, in which case <code>max_age_hours</code> is
                            <code>0</code> in the response.
                        </li>
                        <li>
                            <code>realm</code> (optional): only include dominions currently in this realm number. An
                            unknown realm number returns <code>422</code>.
                        </li>
                        <li>
                            Each dominion's <code>ops</code> always has all eight keys:
                            <code>clear_sight</code>, <code>revelation</code>, <code>castle_spy</code>,
                            <code>barracks_spy</code>, <code>survey_dominion</code>, <code>land_spy</code>,
                            <code>vision</code> and <code>disclosure</code>. A type your realm has not gathered (or that is older than
                            <code>max_age_hours</code>) is <code>null</code>.
                        </li>
                        <li>
                            Each op carries the same fields as the Op Center's Copy Ops export, with a
                            <code>created_at</code> timestamp. The keys are the in-game spell and espionage
                            operation keys, where Copy Ops uses shorter names (<code>status</code>,
                            <code>castle</code>, <code>barracks</code>, <code>survey</code>, <code>land</code>).
                            The example below is shortened.
                        </li>
                    </ul>
<pre class="bg-body-tertiary border rounded p-2"><code>{
    "generated_at": "2026-09-30T12:00:00Z",
    "max_age_hours": 12,
    "realm": null,
    "dominions": {
        "5678": {
            "id": 5678,
            "name": "Target Dominion",
            "realm": 12,
            "race": "Nomad",
            "ops": {
                "clear_sight": {
                    "land": 250,
                    "military_unit1": 42,
                    "race_name": "Nomad",
                    "realm": 12,
                    "name": "Target Dominion",
                    "created_at": "2026-09-30T11:30:00Z"
                },
                "revelation": {
                    "spells": [],
                    "created_at": "2026-09-30T09:00:00Z"
                },
                "castle_spy": {
                    "created_at": "2026-09-30T10:15:00Z"
                },
                "barracks_spy": null,
                "survey_dominion": null,
                "land_spy": null,
                "vision": null,
                "disclosure": null
            }
        }
    }
}</code></pre>

                    <h5 class="fw-bold mb-1" id="dominions-me-op-center-target">Dominion Overview</h5>
                    <p class="mb-2"><span class="badge text-bg-success">GET</span> <code>/dominions/me/op-center/{target}</code></p>
                    <p class="mb-1">
                        The latest op of each type for one dominion. The response has a single
                        <code>dominion</code> object in place of <code>dominions</code>.
                    </p>
                    <ul>
                        <li>
                            Not available for your own dominion or realmies, which return <code>422</code>
                            <code>same_realm</code>; use <a href="#dominions-me-advisors">Realm Advisors</a> for them.
                        </li>
                        <li>
                            <code>max_age_hours</code> (optional, default 0): only include ops gathered within this
                            many hours. <code>0</code> means no age limit, so by default you get the latest op of
                            each type however old it is.
                        </li>
                        <li>
                            Returns <code>404</code> when your realm has no ops on that dominion within the age
                            limit.
                        </li>
                    </ul>

                    <h5 class="fw-bold mb-1" id="dominions-me-op-center-target-type">Op Archive</h5>
                    <p class="mb-2"><span class="badge text-bg-success">GET</span> <code>/dominions/me/op-center/{target}/{type}</code></p>
                    <p class="mb-1">
                        The history of one op type for one dominion: every op of that type your realm has gathered
                        this round, newest first. <code>{type}</code> is one of <code>clear_sight</code>,
                        <code>revelation</code>, <code>castle_spy</code>, <code>barracks_spy</code>,
                        <code>survey_dominion</code>, <code>land_spy</code>, <code>vision</code> or
                        <code>disclosure</code>; anything else returns <code>422</code>. Not available for your own
                        dominion or realmies, which return <code>422</code> <code>same_realm</code>.
                    </p>
                    <ul>
                        <li>
                            <code>max_age_hours</code> (optional, default 0): only include ops gathered within this
                            many hours. <code>0</code> means no age limit.
                        </li>
                        <li><code>limit</code> (optional, default 100, maximum 500): number of ops to return.</li>
                        <li>
                            Each entry in <code>ops</code> has the same fields as that type has in the endpoints
                            above. <code>ops</code> is an empty list when your realm has none.
                        </li>
                    </ul>
<pre class="bg-body-tertiary border rounded p-2 mb-0"><code>{
    "generated_at": "2026-09-30T12:00:00Z",
    "max_age_hours": 0,
    "dominion": {"id": 5678, "name": "Target Dominion", "realm": 12, "race": "Nomad"},
    "type": "barracks_spy",
    "ops": [
        {"created_at": "2026-09-30T10:15:00Z"},
        {"created_at": "2026-09-29T22:40:00Z"}
    ]
}</code></pre>
                </div>
            </div>

        </div>
    </div>
@endsection
