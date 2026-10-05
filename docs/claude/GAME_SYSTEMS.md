# Game Systems Reference

## Hourly Tick System

The game tick (`php artisan game:tick`) runs at :00. Production for a round commits atomically. `RoundTickRun` stores the latest attempted hour and prevents duplicate application. Source: `src/Services/Dominion/TickService.php` and `src/Console/Kernel.php`.

### Production (`TickService::performTick`)

1. Create or advance the round's checkpoint under its own row lock. Older requests are skipped; advancing to a newer hour resets its completion markers.
2. Lock and recheck the checkpoint, then lock eligible dominions in ID order. Read the business `rounds` row without a lock. Eligibility uses protection, account locks and abandonment at the requested hour.
3. Rebuild any missing prediction from the locked dominion before advancing resources or timers.
4. In one transaction, reset daily allowances when due, apply predicted resources, decrement spell/queue timers, produce spell/racial units, record consumed predictions, clean completed queues/spells, persist notifications, calculate the next prediction/networth, and mark production complete.
5. Roll back the entire production transaction on failure. Record failure details only if the checkpoint still belongs to that incomplete attempt. Restore logical time and calculator modes on every exit.

`DominionSaved` suppresses intermediate recalculation while the batch runs. Relations are loaded in batches, and history records the prediction actually applied. Web notifications commit with production; eligible email enters the outbox. Protection uses an individual dominion transaction, can advance multiple steps per wall-clock hour, and does not use scheduled checkpoints.

### Maintenance and Scheduling

`tickHourly()` attempts the current hour for each active round. It does not reconstruct missed scheduler hours, replay ended rounds, or block gameplay because a checkpoint is failed or overdue. A later hour can proceed after an earlier failure. The checkpoint retains only the latest attempt; logs retain earlier errors. Same-hour reruns skip completed production.

After production commits, round maintenance runs in a separate transaction: wars, abandonment, hero battles/tournaments, raids, valuables and daily round tasks. Its `maintenance_completed_at` marker commits with those effects. A same-hour rerun can retry failed maintenance without repeating production or completed maintenance. A newer hour supersedes unfinished older maintenance. Maintenance failure cannot roll back production.

Failures are isolated between rounds and setup phases, then reported by the command. Realm assignment and NPD generation use `RoundSetupService` receipts, unique per round/operation, so repeated commands do not duplicate completed setup.

### Daily Tasks and Predictions

Daily allowances reset in the production transaction. At the round start hour, maintenance moves inactive dominions to the graveyard, spawns wonders every three days, and updates realm/raid active-player counts. `DailyRankingsAndStatsJob` remains separately dispatched before `tickHourly()`.

Each dominion has a `dominion_tick` prediction refreshed after actions and ticks. `precalculateTick()` works on a clone, refreshes volatile relations and restores calculator modes in `finally`. Batch callers can pass `stateIsFresh=true` to reuse relations already loaded together.

### Gameplay and Rollout

Normal player/AI actions retain their existing transaction boundaries. They do not acquire a new shared round lock or an actor lock at request entry, and failed checkpoints do not reject actions. Writes touching dominions currently being ticked can still wait for database locks. This change does not redesign existing action-to-action concurrency or stale validation.

Hourly activity recording uses a short transaction with `FOR UPDATE SKIP LOCKED` and a quiet save. If the dominion is busy, the page continues and a later request can record activity; this bookkeeping does not recalculate predictions. Protection advance/undo and imports lock only their own dominion.

Apply the `notification_outbox`, `round_tick_runs`, and `round_setup_runs` migrations before activation. Drain old scheduler/game/queue workers so old and new code cannot overlap. Let the next scheduled :00 command initialize checkpoints: new records cannot identify work already completed by legacy workers. Use a shared scheduler cache across servers. Database checkpoint locks enforce duplicate protection independently of scheduler locks.

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

`NotificationService::queueNotification()` buffers events. Ordinary actions use `sendNotifications()`. Tick/setup calls to `persistQueuedNotifications()` write web notifications in the same game transaction and store only email that is enabled and eligible at event time. `withDeferredDelivery()` applies this behavior to maintenance/setup calls. Web-only events create no outbox row or background job. The tick/setup receipt protects web writes from replay, and protection undo removes its transactional notifications.

The background `game:notifications:deliver` command sends due email batches directly, independently of the configured queue driver. Defaults: at most 100 rows per run and a 30-second budget checked before starting each delivery. SMTP uses a separate mailer with a socket timeout of `MAIL_OUTBOX_SMTP_TIMEOUT` (default 10 seconds); other configured transports retain their settings. The budget is not a hard deadline for an in-flight send. Failed sends become eligible again after five minutes; the command reports failure after handling the remaining rows within its budget.

- Unique `(operation_key, dominion_id, category)` preserves an email batch on replay.
- Delivery checks current protection and email preferences again. `realm_assignment` remains the protection exception; opting in later does not create email for older events.
- Email delivery is at least once: a crash after mail-server acceptance but before `delivered_at` commits can repeat it. Web notifications never wait for email and are not replayed by the delivery command.
- Hourly email displays `event_at`, not its delayed delivery time.

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
- `MiscController` holds an exclusive lock on the protection dominion across manual counter changes and `performTick()` or `revertTick()`; it does not lock the round or a scheduled checkpoint.
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
