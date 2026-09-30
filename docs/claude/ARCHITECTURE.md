# Architecture Reference

## Directory Structure

```
src/                          # Main application code (namespace: OpenDominion\)
  Application.php             # Custom Laravel Application class
  Models/                     # 67 Eloquent models
  Services/                   # Business logic layer
    Dominion/                 # Dominion-specific services
      Actions/                # 18 action service classes (one per game action)
      API/                    # API calculation services
    Realm/                    # Realm-level services
    Activity/                 # User activity tracking
  Calculators/                # Pure computation (no side effects)
    Dominion/                 # Dominion-level calculators (16)
    Actions/                  # Action cost/limit calculators (6)
  Helpers/                    # 20 domain utility classes (perk descriptions, building maps, etc.)
  Factories/                  # Entity creation (DominionFactory, RealmFactory, RoundFactory)
  Mappers/Dominion/           # InfoMapper - transforms dominion data for display and stored info ops
  Mappers/                    # GameEventMapper - public API representation of game events
  Http/
    Controllers/              # 61 controllers
      Dominion/               # Game action controllers (one per feature)
      Staff/                  # Admin/moderator controllers
      Auth/                   # Authentication controllers
      Api/V1/                 # Public read-only API (RoundController, OpCenterController)
    Middleware/               # 10 middleware classes
    Requests/                 # 35+ form request validation classes
  Console/Commands/           # Artisan commands (game:tick, game:ai, game:data:sync, etc.)
  Events/                     # Laravel events (DominionSaved, InfoOpCreating, User*)
  Listeners/                  # Event listeners
  Providers/                  # Service providers (AppServiceProvider, EventServiceProvider, ComposerServiceProvider)

app/                          # Laravel resources
  config/                     # Configuration files
  data/                       # Game data (YAML/JSON)
    races/                    # 32 race definition files (.yml)
    techs/                    # Tech tree (v2.yml)
    heroes/                   # Per-race hero names (.json)
    quickstarts/              # 115+ starting build templates (.json)
    spells.yml                # 100+ spell definitions
    wonders.yml               # Wonder definitions
    heroes.yml                # Hero upgrade definitions
  database/migrations/        # 120+ migration files
  resources/
    views/                    # Blade templates
      layouts/                # 3 layouts: master (game), topnav (public), staff (admin)
      pages/                  # Route-specific views
        dominion/             # 67 game page views
        auth/                 # Login, register, password reset
        staff/                # Admin/moderator pages
        scribes/              # Public game documentation
      partials/               # Reusable components
    sass/                     # SCSS (Bootstrap 5)
    js/                       # JavaScript (jQuery, Bootstrap 5, Select2)
  routes/
    web.php                   # Main routes (~411 lines)
    api.php                   # API routes

tests/                        # PHPUnit tests
  Unit/                       # Calculator and logic tests
  Feature/                    # Game mechanic tests
  Http/                       # Controller endpoint tests
  Traits/                     # Test data creation helpers
```

## Request Flow

```
HTTP Request
  → Kernel middleware (CSRF, session, auth)
  → Custom middleware (UpdateUserLastOnline, ShareSelectedDominion)
  → Route middleware (auth, dominionselected, role:*)
  → FormRequest validation (e.g., InvadeActionRequest)
  → Controller (orchestration only)
  → Service (business logic, DB transactions)
    → Calculators (pure computation)
    → QueueService (deferred resource delivery)
    → HistoryService (delta recording)
    → NotificationService (queued notifications)
  → Redirect with flash message (or GameException → error redirect)
```

## Key Architectural Patterns

### Action Service Pattern
Every game action follows this structure:
1. Controller receives validated request
2. Calls `{Action}ActionService::{action}(Dominion, ...params)`
3. Service validates with `DominionGuardsTrait` (locked? tick in progress? disabled?)
4. Calls calculators for costs/limits
5. Modifies dominion state within `DB::transaction()`
6. Records history via `HistoryService::record()`
7. Queues notifications via `NotificationService`
8. Returns `['message' => '...', 'alert-type' => 'success']` or throws `GameException`

### Calculator Pattern (Raw + Multiplier)
Most calculations separate base values from multipliers:
```
getResourceProduction()     = getResourceProductionRaw() * getResourceProductionMultiplier()
getResourceProductionRaw()  = base per building * building count
getResourceProductionMultiplier() = 1 + race_perk + spell_perk + tech_perk + wonder_perk + ...
```

### Perk System
Races, units, spells, techs, wonders, and hero upgrades all use a consistent perk pattern:
- Many-to-many with `*_perk_types` tables via `*_perks` pivot (with `value` column)
- Accessed via `getPerkValue($key)` / `getPerkMultiplier($key)` methods
- Calculators aggregate perks from all sources to compute final bonuses

### Queue System
Deferred resource delivery (12h default):
- Sources: `construction`, `exploration`, `training`, `invasion`, `operations`
- Each tick: hours decrement by 1; when hours = 0, resources apply to dominion
- `QueueService::setForTick(true)` excludes next hour from calculations (prevents double-counting)

### History Tracking
Every state change records a delta in `dominion_history`:
- Delta contains only changed attributes (old values subtracted from new)
- Used for: audit trails, admin rollback, state reconstruction at any point in time
- Tracks IP + device for security

### View Architecture
- **3 layouts**: `master` (game with sidebar), `topnav` (public), `staff` (admin)
- **ComposerServiceProvider**: Injects data into partials (sidebar counts, calculator results, etc.)
- **ShareSelectedDominion middleware**: Makes `$selectedDominion` globally available
- **View stacks**: `@stack('page-styles')`, `@stack('page-scripts')`, `@stack('inline-scripts')`

## Route Groups

### Public Routes (no auth)
- `/` - Landing page
- `/about`, `/terms`, `/privacy`, `/user-agreement` - Static pages
- `/scribes/*` - Public game reference (races, construction, espionage, magic, techs, heroes, wonders)
- `/valhalla/*` - Historical round rankings and statistics
- `/auth/*` - Login, register, password reset, Discord OAuth

### Gameplay Routes (prefix: `/dominion`, middleware: `auth` + `dominionselected`)
- **Status & Info**: status, advisors (magic/military/production/rankings/statistics), realm, world, search, op-center, town-crier, rankings
- **Economy**: explore, construct, destroy, rezone, improvements, bank, techs, daily bonuses
- **Military**: military (train/draft/release), invade, heroes, hero-battles, hero-tournaments
- **Magic & Espionage**: magic, espionage, black-guard
- **Community**: council, forum, message-board, bounty-board, government
- **Raids & Wonders**: raids, raid-leaderboard, wonders
- **Management**: settings, automation, journals, protection
- **Lifecycle**: abandon, restart

### API Routes (`app/routes/api.php`, prefix: `/api/v1`)
- No auth, `throttle:60,1`: `GET /v1/pbbg` (public game info), `POST /v1/bugsnag`, `GET /v1/time` (server time for the in-game ticker)
- `api` + `auth` (session): `GET /v1/dominion/invasion` (invasion calculation, also `dominionselected`), `GET /v1/calculator/defense|offense` (battle calculators), `GET /v1/user/feedback` (player endorsements)

### Public Read-Only API (prefix: `/v1`, controllers in `src/Http/Controllers/Api/V1/`)
- `GET /v1/rounds`, `/v1/rounds/{round}/dominions` (includes `guard`: royal/elite, never black), `/v1/rounds/{round}/realms` (held wonders with power, current wars from `RealmWar::active()` listed under both realms; `apikey:optional` - a key for the holder's realm or a realm at war with it gets exact `WonderCalculator::getCurrentPower()`, everyone else `getApproximatePower()`), `/v1/rounds/{round}/events` - No auth, `throttle:60,1` (`RoundController`)
- `GET /v1/dominions/me`, `/v1/dominions/me/op-center`, `/v1/dominions/me/op-center/{target}`, `/v1/dominions/me/op-center/{target}/{type}` (history of one op type) - `apikey` middleware (`DominionApiKey`, `X-API-Key` header or Bearer), `throttle:60,1` (`OpCenterController`). `me` returns `server_time`, whitelisted own-dominion `resources`, `military` (units at home plus returning via `getTotalUnitsForSlot()`, spy/wizard strength, OP/DP modifiers as percentages, offense/defense spy and wizard ratios, all from `MilitaryCalculator`), `statistics` (round totals from `stat_total_*` columns; `{resource}_spent` sums that resource's `stat_total_{resource}_spent_*` columns), `hourly` (`ProductionCalculator`: production, consumption, decay, net change, all per hour) and `population` (`PopulationCalculator`), and a `links` block with URLs for the round endpoints. Its round payload (not `/rounds`) carries `day`/`hour` (`Round::daysInRound()`/`hoursInDay()`, null unless `isActive()`) and `duration_days`.
- Op-center payloads are built by `InfoOpAssemblerService` from the stored info op snapshots (which `InfoMapper` produced when the op was cast), formatted like the Op Center's Copy Ops JSON but keyed by the stored op type (`clear_sight`, `barracks_spy`, ...) rather than Copy Ops' short names. Every type key is present, `null` when missing; revelation casters are obfuscated. `op-center` defaults to `max_age_hours=12`; `{target}` and `{target}/{type}` default to 0 (no limit). `{type}` returns full history (all `latest` values), `limit` default 100 / max 500. For the key's own dominion or a realmie, `{target}` returns live data via `InfoOpAssemblerService::assembleFromAdvisors()` (the in-game realm advisors view, gated by `Dominion::inRealmAndSharesAdvisors()`, 403 `advisors_not_shared` otherwise), and `{target}/{type}` returns 422 `same_realm`. The `op-center` list only ever contains info ops.
- Every V1 error is `{"error": code, "message": ...}`. `src/Exceptions/Handler.php` renders `ModelNotFoundException` (unknown `{round}`/`{target}`) as 404 `not_found` and `ThrottleRequestsException` as 429 `rate_limited` for routes named `api.rounds.*` / `api.dominions.*`, so model class names never leak.
- All V1 timestamps use `toIso8601ZuluString()` (e.g. `2026-09-30T11:45:00Z`).
- Throttling is keyed per IP (no session on these routes) and the counter is shared by every throttled API route, including `/v1/time`. V1 feature tests disable `ThrottleRequests`.
- `/v1/rounds/{round}/events` output is built by `src/Mappers/GameEventMapper.php`: a whitelist that exposes only what the Town Crier shows per event type (mirrors `partials/dominion/game-event.blade.php`). Only types in `GameEventMapper::PUBLIC_TYPES` are returned (sentient wonder attacks, `wonder_invasion`, are excluded); `?type=` filters by one or more of them. Stored `data` is never passed through; morph types are returned as snake-cased basenames (`realm_war`). Dominion and realm participants also get `source_name`/`source_realm_number` and `target_name`/`target_realm_number` (null for other morph types and for neutral wonder targets). When adding an event type or changing what the Town Crier displays, update the mapper, its test, and the docs page.
- `apikey` accepts an `optional` mode (`apikey:optional`): no key continues unauthenticated (and clears any bound `api.dominion`), a sent key is still validated.
- `roundstarted` middleware (`ApiRoundStarted`) is applied per route to every V1 endpoint except `/v1/rounds` and `/v1/dominions/me`: before the round's `start_date` they return 403 `round_not_started`. The round comes from the `{round}` parameter or the API key's dominion. Add it to any new V1 endpoint that exposes in-round data.
- Public docs page at `/api-docs` (route `api-docs`, `HomeController@getApiDocsPage`, view `pages/api-docs.blade.php`), no auth required. Linked only from the API Key card on the dominion settings page. `ApiDocsPageTest` fails if a V1 route is added without being documented there — update the page when adding or changing endpoints.

### Staff Routes (prefix: `/staff`, middleware: `auth` + `role:Developer|Administrator|Moderator`)

**Controllers**: `src/Http/Controllers/Staff/`

- **StaffController** - Overview dashboard, audit logs
- **Administrator/DominionController** - Dominion CRUD, anti-cheat logs
- **Administrator/OriginLookupController** - IP Lookups page with per-round tier counts and round-wide findings for looked-up IPs
- **Administrator/UserController** - User search (display name/email), user detail (basic info, rating/affinities, origins with IPQS VPN/fraud score), on-demand IPQS origin lookup
- **Administrator/RaidController** - Full raid CRUD (create/edit raids, objectives, tactics)
- **Administrator/HeroTournamentController** - Hero tournament CRUD (create/edit/delete tournaments, view participants)
- **Moderator/DominionController** - Game event viewing, activity logs, dominion locking/unlocking, combined anonymizer flags across a dominion's looked-up IPs (no IPs or scores exposed)

Role-based access uses Spatie permission middleware (`role:Administrator`, `role:Moderator`). Staff layout is `layouts/staff.blade.php` with a 2-column grid (side nav + content).

## Data Loading Pipeline

```
YAML/JSON files in app/data/
  → php artisan game:data:sync (Symfony YAML parser)
  → Database tables: races, units, spells, techs, wonders, heroes, hero_upgrades
  → Models with perk relationships
  → Helpers provide human-readable descriptions
  → Calculators consume perks for game mechanics
```

## Service Registration

All services registered as **singletons** in `AppServiceProvider`. Dependency injection via constructors throughout. No static calls or service locator pattern (except rare `app()` calls for late binding in calculators).

## Scheduled Tasks (Console Kernel)

| Schedule | Command | Purpose |
|----------|---------|---------|
| Hourly (:00) | `game:tick` | Main game tick processing |
| Hourly (:30) | `game:ai` | AI/NPC dominion actions |
| Every 5 minutes (:05-:25, :35-:55) | `game:ai:invade` | Attacker NPD invasions at each bot's hourly minute |
| Daily (01:20) | `backup:clean` | Clean old backups |
| Daily (01:40) | `backup:run` | Create backup |
