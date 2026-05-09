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
  Mappers/Dominion/           # InfoMapper - transforms dominion data for display/API
  Http/
    Controllers/              # 57 controllers
      Dominion/               # Game action controllers (one per feature)
      Staff/                  # Admin/moderator controllers
      Auth/                   # Authentication controllers
    Middleware/               # 9 middleware classes
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
    sass/                     # SCSS (Bootstrap 5 via AdminLTE 4)
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

### API Routes (prefix: `/v1`, middleware: `api` + `auth`)
- `GET /v1/pbbg` - Public game info
- `GET /v1/dominion/invasion` - Invasion calculation
- `GET /v1/calculator/defense|offense` - Battle calculators
- `GET /v1/user/feedback` - Player endorsements

### Staff Routes (prefix: `/staff`, middleware: `auth` + `role:Developer|Administrator|Moderator`)

**Controllers**: `src/Http/Controllers/Staff/`

- **StaffController** - Overview dashboard, audit logs
- **Administrator/DominionController** - Dominion CRUD, anti-cheat logs
- **Administrator/UserController** - User CRUD, account takeover capability
- **Administrator/RaidController** - Full raid CRUD (create/edit raids, objectives, tactics)
- **Administrator/HeroTournamentController** - Hero tournament CRUD (create/edit/delete tournaments, view participants)
- **Moderator/DominionController** - Game event viewing, activity logs, dominion locking/unlocking

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
| Daily (01:20) | `backup:clean` | Clean old backups |
| Daily (01:40) | `backup:run` | Create backup |
