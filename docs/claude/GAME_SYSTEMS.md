# Game Systems Reference

## Hourly Tick System

The game tick (`php artisan game:tick`) runs at :00. Each round/hour commits as one transaction; `RoundTickRun` prevents a completed hour from being applied twice. Source: `src/Services/Dominion/TickService.php` and `src/Console/Kernel.php`.

### Tick Flow (`TickService::performTick`)

```
1. Register the round/hour in round_tick_runs under an exclusive round lock.
   An earlier incomplete hour or a gap blocks registration of a later hour.
2. In one transaction (up to five attempts on database concurrency errors):
   a. Lock the round exclusively, then eligible dominions in ID order.
      Eligible: protection finished, unlocked, abandonment not yet effective
      at this tick's logical time. Require a prediction row for every dominion.
   b. Reset daily bonuses at the round start hour.
   c. Apply dominion_tick through a joined batch UPDATE; decrement spell
      durations and queue hours for the same locked dominion set.
   d. Apply special spell effects and racial unit production.
   e. Record the consumed prediction in history before calculating its replacement.
      Delete expired spells/completed queues and persist notification outbox rows.
   f. Reload queues/spells in batches; calculate the next prediction and networth.
   g. Expire wars, clear abandoned dominions' votes/API keys, process hero battles,
      tournaments, raid rewards, valuables, and daily round tasks.
   h. Mark the ledger hour complete in this same transaction.
3. On failure, roll back game state/history/outbox and retain the pending ledger
   row with attempt/error details. Restore logical clock and calculator modes.
```

`DominionSaved` skips per-save recalculation while `isProcessingTick()` is true; the tick explicitly recalculates after its batch changes. The guard is cleared before round maintenance, so maintenance saves still refresh affected predictions. Scheduled tick/setup transactions persist notifications without dispatching delivery jobs inline. Single-dominion ticks still in protection write web notifications in their transaction so undo can remove them; protection email remains disabled.

### Recovery and Setup

- `game:tick --recover` runs every minute in the background. `recoverHourlyTicks(true)` handles only rounds already present in the ledger; it does not run pre-round setup or daily rankings jobs.
- `getDueTickHours()` starts at the earliest pending hour or the hour after the latest completion. Hours run in order under their original logical clock, including after round end; the final due hour is clamped to the hour containing `end_date - 1 second`.
- The main hourly command can bootstrap rounds without a ledger at the current due hour. It does not reconstruct all pre-deployment hours.
- Realm assignment and NPD generation use `RoundSetupService` receipts, unique per round/operation. Each callback receives the freshly locked round; setup changes, deferred notifications, and the receipt commit together. Replays skip completed setup.

### Daily Tasks

At the round start hour, the round transaction moves inactive dominions to the graveyard, spawns wonders every three days, and updates realm/raid active-player counts. `game:tick` dispatches `DailyRankingsAndStatsJob` before `tickHourly()`, preserving the existing rankings/statistics ordering. The job separately updates daily/Valor rankings and daily statistics outside the round tick transaction; completion timing depends on the queue driver.

### Pre-calculation System

Each dominion has a `dominion_tick` row containing the next hour's changes. It is refreshed after actions and ticks, then consumed by the joined batch update. `precalculateTick()` works on a clone, uses freshly loaded volatile relations, and restores calculator tick mode in `finally`. The tick passes `stateIsFresh=true` after its eager loads to avoid repeating those reads per dominion.

### Mutation Coordination and Rollout

`RoundMutationService` acquires a shared round lock before an exclusive actor-dominion lock and reloads state before validation. This permits actions on different dominions concurrently while excluding a whole-round tick. HTTP mutations, explicitly marked GET mutations, AI actions/invasions, and protection actions participate. Active rounds with pending/overdue ledger hours reject mutations, including protection changes; a round with no ledger remains available during bootstrap. Read-only pages do not hold a request-wide transaction; hourly activity tracking uses a short coordinated quiet save, preserving pending predictions during recovery.

Apply the `notification_outbox`, `round_tick_runs`, and `round_setup_runs` migrations before activating this code. Stop/drain old scheduler and game/queue workers before starting the new version; old workers do not honor the new locks/receipts and must not overlap new workers. During deployment between hours, use `--recover` only for existing ledger rounds and let the next scheduled :00 command bootstrap others. Do not manually run the main `game:tick` mid-hour during activation: new tick/setup receipts cannot recognize work already completed by legacy workers. Setup windows and rankings/statistics remain separate from hourly ledger recovery. Scheduler cache locks reduce overlap; database locks and unique receipts enforce correctness.

## Queue System

Resources are queued with a delivery delay (typically 12 hours).

| Source | What's Queued | Default Hours | Processing |
|--------|--------------|---------------|------------|
| construction | Building types | 12 | Buildings added to dominion |
| exploration | Land types | 12 | Land added to dominion |
| training | Unit types, spies, wizards, etc. | 12 (9 for basic) | Units added to military |
| invasion | Land, units, resources returning | 12 (9-12 by unit perk) | Land/units/resources return |
| operations | Spell/spy operation tracking | varies | No completion notification |

Each tick applies the incoming resources already included in the consumed prediction, decrements hours, then deletes completed queue rows. Cleanup does not apply the resources a second time.

## Event System

### Laravel Events

| Event | Listeners | Trigger |
|-------|-----------|---------|
| `DominionSavedEvent` | `DominionSaved` → recalc networth + precalculate tick, unless tick batch processing is active | Any dominion save |
| `InfoOpCreatingEvent` | `InfoOpCreating` → marks previous ops as non-latest | New info op created |
| `UserRegisteredEvent` | `SetUserDefaultSettings`, `SendUserRegistrationNotification` | User registration |
| `UserLoggedInEvent` | `ActivitySubscriber` | Login |
| `UserLoggedOutEvent` | `ActivitySubscriber` | Logout |
| `UserFailedLoginEvent` | `ActivitySubscriber` | Failed login |
| `UserActivatedEvent` | `ActivitySubscriber` | Account activation |

### Game Events (GameEvent model)
Persistent records displayed in Town Crier:
- **Types**: invasion, war_declared, war_canceled, wonder_spawned, wonder_attacked, wonder_destroyed, raid_attacked, abandoned
- **Polymorphic source/target**: Dominion, RoundWonder, Realm, RealmWar

## Notification System

`NotificationService::queueNotification()` buffers events. Ordinary actions use `sendNotifications()`; scheduled ticks and setup instead persist `NotificationOutbox` rows in their game transaction. `withDeferredDelivery()` routes maintenance/setup calls to `sendNotifications()` into that same outbox.

The background `game:notifications:deliver` command runs every minute and dispatches due batches (default limit 100); `DeliverNotificationOutbox` delivers them. Scheduled ticks perform no inline delivery, including with a synchronous queue driver. A single-dominion tick still in protection writes only its in-game notifications synchronously in the game transaction; these roll back on failure and are removed by protection undo. A tick that finishes protection uses the outbox. Failed submissions/deliveries become eligible for later recovery through `available_at`.

- Unique `(operation_key, dominion_id, category)` preserves the original payload on replay.
- Web notifications and `web_delivered_at` commit together under the outbox row lock, preventing duplicate web delivery.
- Email and the final receipt use a separate transaction. Email is at least once: a crash after mail-server acceptance but before receipt commit can repeat it.
- `email_allowed` records protection eligibility when the event occurred; delivery also checks current protection and user preferences. `realm_assignment` is the protection exception. Hourly email displays `event_at`, not its delayed delivery time.

### Channels
- **WebNotification** - In-game notification display
- **HourlyEmailDigestNotification** - Batched hourly email
- **IrregularDominionEmailNotification** - Immediate email for urgent events

### Categories
- `general` - System notifications
- `hourly_dominion` - Tick-related (resource production, spell expiry, queue completion)
- `irregular_dominion` - Action-triggered (invasion results, bounty collected)
- `irregular_realm` - Realm events (war declared, wonder destroyed)

User settings control per-type, per-channel delivery: `notifications.{category}.{type}.{ingame|email}`

## Guard System

Three tiers restricting attack range for competitive balance:

| Guard | Range Restriction | Join Delay | Leave Delay | Requirements |
|-------|-------------------|------------|-------------|--------------|
| Royal Guard | 60% | 24h | - | 48h into round |
| Elite Guard | 75% | 24h | - | Royal Guard member |
| Black Guard | None (war ops) | 12h | 12h + 12h | 48h into round |

## War System

```
Declare War → 24h wait → War Active → (up to 120h total)
                                     ↓
                              Cancel Request → 24h wait → War Inactive → 12h cooldown
                                                                        ↓
                                                                 Can Redeclare (48h total wait)
```

War bonuses: increased prestige, OP/DP multipliers, tech gain bonuses during active wars.

## Protection System

New dominions start in protection:
- Cannot be attacked or targeted by hostile ops
- Cannot attack or perform hostile actions
- Can leave protection after 24h (`WAIT_PERIOD_DURATION_IN_HOURS`)
- Daily bonuses available during protection (platinum, land, automated actions)
- `MiscController` holds the shared round/exclusive dominion transaction across manual counter changes and `performTick()` or `revertTick()`; single-dominion ticks use the same lock order, avoiding a round-lock upgrade.
- Protection links carry optional `expected_protection_ticks_remaining`, checked after locking to reject stale/repeated clicks. Clients omitting it remain supported; it is a counter check, not a permanent operation ID.
- `AutomationService::processLog()` coordinates the import and keeps each hour atomic. Expected game errors preserve completed hours, roll back the failed hour, and refresh the dominion for recovery.

## Wonder System

Realm-level PvE objectives that grant bonuses:

### Lifecycle
1. **Spawn**: 3 initial wonders at round start (2 Tier1, 1 Tier2)
2. **Growth**: New wonders spawn every 3 days (max 6 concurrent)
3. **Attack**: Dominions deal damage, tracked per-dominion and per-realm
4. **Capture**: When power reaches 0, attacking realm claims wonder
5. **Rebuild**: Wonder rebuilds with new power based on round progress
6. **Sentient**: Some wonders attack back (top 3 damage-dealing realms lose land)

### Tiers
- **Tier2** (75,000 power): Days 0-8
- **S-tier** (150,000 power): Day 9 only (city_of_gold, fountain_of_youth, horn_of_plenty)
- **Tier1** (150,000 power): Day 10+

## Round Perks

Storyline-driven bonuses applied to every dominion in a round, configured per round in the admin UI (Staff → Rounds → show page).
- Stored in `round_perks`; each row = key + value, optionally limited by race alignment and an inclusive `from_day`/`until_day` window (`Round::daysInRound()`)
- Calculators add `$dominion->getRoundPerkMultiplier('key')` / `getRoundPerkValue('key')` as an explicit `// Round Perks` line next to the racial bonus
- Supported keys live in `RoundPerkHelper::getPerkTypes()`; a new perk type = registry entry + calculator call site. Current keys: offense, defense, platinum/food/lumber/mana/ore/gem/tech_production, wartime_platinum_production (flat %, once if the realm has any active incoming/outgoing war), construction_cost, explore_platinum_cost, invest_bonus, max_population, alchemy_platinum_production_raw, farm_food_production_raw, tower_mana_production_raw
- Status page shows the round description + all perks (wide card) and active perk values under racial perks in the Information card

## Raid System

Time-bound cooperative objectives with multiple tactic types:

### Structure
```
Raid → RaidObjective(s) → RaidObjectiveTactic(s)
                        → RaidContribution(s) per dominion/realm
```

### Tactic Types
hero, investment, exploration, espionage, magic, invasion

### Rewards
- Distributed when raid ends via `RaidService::processCompletedRaids()`
- Participation + completion rewards scaled by contribution
- Max 15% per realm, 15% per player

## Hero System

### Classes
8 basic + 2 advanced hero classes with passive perks and active combat abilities.

### Experience Sources
- Invasions, exploration, spying, magic operations
- Multiplied by: shrines, racial perks, wonder bonuses

### Combat
- 1v1 turn-based battles with time bank (2h default)
- Tournaments with bracket elimination
- Processed during hourly tick

### Upgrades
40+ hero upgrades with level/class requirements, granting permanent perk bonuses.

## Realm Assignment Algorithm

Pre-round player distribution (RealmAssignmentService):

1. Close/dissolve packs
2. Load players with ratings and favorability data
3. Calculate realm count (8-14) based on large pack distribution
4. Place large packs as realm seeds
5. Segregate non-Discord solo players (packed players remain with their packs)
6. Assign remaining packs by compatibility scoring
7. Fill solos by rating balance + playstyle diversity
8. Optimize via 50 iterations of random swap improvement

### Scoring
- **Compatibility**: Favorability matrix + weighted playstyle balance (attacker/converter/explorer/ops); players without a known affinity profile are excluded from playstyle sums and denominators
- **Balance**: Rating deviation from target average
- **Size**: Penalizes full realms, rewards undersize

## AI/NPC System

### Non-Player Dominions (NPDs)
- Spawned in The Graveyard (realm 0) for active-soon rounds at 80% of the human dominion count, with stratified land sizes (420-599 acres, `DominionFactory::NON_PLAYER_LAND_DISTRIBUTION`)
- `DominionFactory::createRandomNonPlayer()` handles name generation/retries; TickService only generates `ai_config` for bots that don't already have one
- Execute building/military/spell actions during `game:ai` command (hourly at :30), skipped per run by `active_chance`
- Draft rate optimized (90% max), elite guard activation at land threshold
- `ai_config['strategy']` selects behaviour; missing = `explorer`

### Explorer NPDs (default)
- Train the defensive unit up to `AIHelper::getDefenseForNonPlayer(round, totalLandIncoming)`, explore, invest, release draftees

### Attacker NPDs (`strategy = attacker`)
- Supported races in `AIHelper::ATTACKER_RACE_SETTINGS` (Orc, Spirit, Vampire); config from `AIHelper::generateAttackerConfig()`
- Unit pairing: defensive `unit2` → offensive `unit4`, defensive `unit3` → offensive `unit1` (per-race `unit_pairs` overrides)
- Orc starts unit3/unit1 and swaps to unit2/unit4 at 600 prestige (`unit_swap`, applied once by `AIService::applyUnitSwap()`); Vampire trains unit2/unit4 (Ghouls/Bloodreavers) all round and casts Feast of Blood before invading
- Build plan: smithy 18%, docks 30-50 when the offensive unit needs boats, towers 9% / ore mines 6% only when a trained unit (including `unit_swap` units) costs mana / ore, unlimited `housing` building (barracks on hill by default, homes on the home land type for Vampire) + one job building (starting homes from spawn are kept); `lumberyard_percentage` overrides the default 3.5% lumberyards (Orc 12%)
- Racial offensive spells live in `attack_spells` and are cast only once the cheap pre-check passes and targets are in range (`AIService::castAttackSpells()`), not kept up hourly
- Spawned attackers start with 300-350 of their `starting_offense` unit (unit1 by default, unit4 for Vampire; factory networth unit1 is zeroed); another 300-350 queued in training over hours 4-9 (`AIHelper::getAttackerIncomingOffense()`), smithies topped up to 18%, lumberyards topped up to `lumberyard_percentage` when set, and races whose offense needs boats also get 36 boats and 20 docks; new buildings are converted from the most common buildings other than homes/docks/smithies/lumberyards so land totals are unchanged (`AIHelper::getAttackerStartingAttributes()`)
- `starting_spec_ratio` is passed to `DominionFactory::createRandomNonPlayer()` to fix the spec/elite defense split (0 = elites only, used by Orc and Vampire); the factory treats Orc as a no-ore race so bots no longer spawn with ore mines
- Hourly order (`game:ai` at :30): unit swap → spells → rezone → construct → train → invest (never explores or releases draftees)
- Invasions run separately via `game:ai:invade` (every 5 minutes during :05-:25 and :35-:55, `AIService::INVASION_MINUTES`); each attacker attempts once per hour at `AIService::getInvasionMinute()` (CRC32 of dominion id + hour, computed in SQL so each run only loads attackers due in that slot), with no activity roll
- Defense goal uses home-guard DP (`AIService::getHomeGuardDefense()`: draftees + non-offensive slots + training queue), so offensive units away or at home don't count; offense is trained only once the goal is met
- Invasion (`AIService::attemptInvasion()`): skipped below 80 morale, at `max_land`, or while units are still returning in the invasion queue; after day 1, a cheap pre-check compares max sendable OP against `getDefenseForNonPlayer()` at `min_range` (75%) of own land as of 12 hours ago (bot goals include troops in training) before loading real targets; targets are bots in the same graveyard realm at ≥ `min_range`, largest first; sends the smallest force with OP > `MilitaryCalculator::getDefensivePowerWithTemples()` (lowest DP-per-OP units first, capped by boats), dry-running 40% / 5:4 / boat rules; one hit per run
- Same-realm invasions are allowed only in realm 0 (`InvadeActionService`)
- Spawn manually: `php artisan game:ai:spawn --round= --race= [--count=1] [--type=explorer|attacker] [--land=] [--prestige=]` (prestige is capped at max(land, 250) every tick, so the command warns when it is set higher)

### Player Automation
- Players can define `ai_config` with tick-based action instructions
- Limited to `DAILY_ACTIONS` = 3 automated actions per day
- Supports: spell casting, building, training, exploring, investing, releasing
