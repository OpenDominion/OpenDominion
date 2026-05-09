# Game Systems Reference

## Hourly Tick System

The game tick (`php artisan game:tick`) runs every hour and is the core game engine.

### Tick Flow (TickService::tickHourly)

```
1. Reset daily bonuses (platinum/land/actions) if round start hour
2. For each active round:
   a. performTick() — batch SQL updates from dominion_tick table:
      - Resources: platinum, food, lumber, mana, ore, gems, tech, boats
      - Military: draftees, units 1-4, spies, assassins, wizards, archmages
      - Land: plain, mountain, swamp, cavern, forest, hill, water
      - Buildings: 16+ types (home, farm, smithy, tower, etc.)
      - Status: prestige, morale, spy_strength, wizard_strength, resilience
      - Meters: fireball_meter, lightning_bolt_meter
      - Stats: production stats, networth, highest_land_achieved
   b. Decrement spell durations by 1
   c. Decrement queue hours by 1
   d. For each dominion:
      - performSpellEffects() — special spell conversions (burning→rejuvenation, cull the weak, etc.)
      - cleanupActiveSpells() — delete expired spells, queue notifications
      - cleanupQueues() — apply completed queue resources, queue notifications
      - precalculateTick() — compute next hour's dominion_tick values + save history
      - sendNotifications() — dispatch all queued notifications
3. Expire wars exceeding 120h
4. Check abandoned dominions (move to graveyard)
5. Process hero battles (HeroBattleService)
6. Process hero tournaments (HeroTournamentService)
7. Process completed raids (RaidService)
8. Assign realms for rounds ready for assignment
9. Spawn NPDs for active-soon rounds
```

### Daily Tasks (within tick, at round start hour)
- Move inactive dominions to graveyard (3+ days old, 3+ days offline)
- Spawn wonders every 3 days
- Update active player counts for realms/raids
- Update daily rankings
- Update Valor rankings

### Pre-calculation System
Each dominion has a `dominion_tick` record that stores projected changes for the next hour.
- Created/updated after every action and after each tick
- Used for batch SQL updates during tick (performance: one UPDATE per column, not per dominion)
- Transaction with 5-retry pessimistic locking

## Queue System

Resources are queued with a delivery delay (typically 12 hours).

| Source | What's Queued | Default Hours | Processing |
|--------|--------------|---------------|------------|
| construction | Building types | 12 | Buildings added to dominion |
| exploration | Land types | 12 | Land added to dominion |
| training | Unit types, spies, wizards, etc. | 12 (9 for basic) | Units added to military |
| invasion | Land, units, resources returning | 12 (9-12 by unit perk) | Land/units/resources return |
| operations | Spell/spy operation tracking | varies | NOT auto-dequeued |

Each tick: hours decrement by 1. When hours reach 0, resources are applied and queue row deleted.

## Event System

### Laravel Events

| Event | Listeners | Trigger |
|-------|-----------|---------|
| `DominionSavedEvent` | `DominionSaved` → recalc networth + precalculate tick | Any dominion save |
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

Two-phase delivery via NotificationService:
1. **Queue phase**: `queueNotification(type, data)` buffers in memory during action processing
2. **Send phase**: `sendNotifications(Dominion, category)` dispatches all queued notifications

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
5. Segregate non-Discord players (preference-based)
6. Assign remaining packs by compatibility scoring
7. Fill solos by rating balance + playstyle diversity
8. Optimize via 50 iterations of random swap improvement

### Scoring
- **Compatibility**: Favorability matrix + playstyle balance (attacker/converter/explorer/ops)
- **Balance**: Rating deviation from target average
- **Size**: Penalizes full realms, rewards undersize

## AI/NPC System

### Non-Player Dominions (NPDs)
- Spawned for active-soon rounds with stratified land sizes (420-599 acres)
- Execute random building/military/spell actions during `game:ai` command (hourly at :30)
- Draft rate optimized (90% max), elite guard activation at land threshold

### Player Automation
- Players can define `ai_config` with tick-based action instructions
- Limited to `DAILY_ACTIONS` = 3 automated actions per day
- Supports: spell casting, building, training, exploring, investing, releasing
