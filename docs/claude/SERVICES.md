# Services & Business Logic Reference

## Action Services (`src/Services/Dominion/Actions/`)

Every game action has a dedicated service class. All follow the same pattern:
- Use `DominionGuardsTrait` for locked/tick/disabled checks
- Inject relevant calculators via constructor
- Accept `Dominion` + action params, return `['message' => ..., 'alert-type' => ...]`
- Throw `GameException` on validation failure
- Record history, queue notifications

| Service | Action | Key Validations |
|---------|--------|-----------------|
| `ExploreActionService` | Land exploration | Affordability, land type limits, max explore during protection |
| `ConstructActionService` | Build buildings | Barren land availability, affordability, land type allocation |
| `DestroyActionService` | Destroy buildings | Owns buildings to destroy |
| `RezoneActionService` | Convert land types | Barren land of source type available |
| `ImproveActionService` | Castle improvements | Resource availability (platinum, lumber, ore, gems) |
| `BankActionService` | Resource exchange | Exchange rates, resource availability |
| `TechActionService` | Research tech | Tech points available, prerequisites met |
| `TrainActionService` | Train military | Affordability, barracks capacity, draft rate |
| `ChangeDraftRateActionService` | Set draft rate | Range 0-90% |
| `ReleaseActionService` | Release draftees | Has draftees to release |
| `InvadeActionService` | **Military invasion** | 50% rule, 40% rule, 5:4 ratio, morale >= 80, boats, defense |
| `SpellActionService` | Cast spells | Mana cost, wizard strength, cooldown, spell availability |
| `EspionageActionService` | Spy operations | Spy strength >= 30%, target in range |
| `GovernmentActionService` | Government actions | Monarch authority, war timing rules |
| `GuardMembershipActionService` | Join/leave guards | Timing requirements, range eligibility |
| `HeroActionService` | Hero management | Hero exists, class change cooldown (96h) |
| `WonderActionService` | Wonder attacks | Units available, wonder exists |
| `DailyBonusesActionService` | Claim daily bonus | Not already claimed today |
| `RaidActionService` | Raid participation | Active raid, valid tactic |

### InvadeActionService (most complex)

Key invasion rules enforced:
- **50% rule**: Attacking OP must exceed 50% of defender DP (scales 40-60% by land ratio)
- **40% rule**: Home DP after attack must be >= 40% of total DP (home + returning)
- **5:4 ratio rule**: Attacking OP cannot exceed home DP * 1.25
- **Morale**: Must be >= 80%
- **Boats**: Water-based units need boats (30 units per boat)
- Constants: `BOATS_SUNK_BASE` = 5%, attacker casualties 8.5% base, defender 3.6-4.8%

## Domain Services (`src/Services/Dominion/`)

### TickService - Game Engine
The hourly heartbeat. See GAME_SYSTEMS.md for full details.

### QueueService
Manages deferred resource delivery queues.
- **Sources**: construction, exploration, training, invasion, operations
- **Key methods**: `queueResources()`, `dequeueResource()`, `getQueueTotal()`, `getQueueAmount()`
- **Magic method**: `get{Source}Queue()`, `get{Source}QueueTotal()` via `__call`
- **Tick mode**: `setForTick(true)` excludes next-hour resources from calculations

### HistoryService
Records dominion state deltas for every action.
- `record(Dominion, deltaAttributes, event)` - Saves changed attributes
- `getDominionStateAtTime(Dominion, DateTime)` - Reconstructs past state
- 50+ event type constants (EXPLORE, CONSTRUCT, TRAIN, INVADE, CAST_SPELL, etc.)
- Tracks IP + device for security

### GovernmentService
Realm governance mechanics.
- Monarch election (>1/3 votes needed)
- Court positions: general, spymaster, magister, mage, jester
- War lifecycle constants:
  - `WAR_ACTIVE_WAIT` = 24h (declaration to active)
  - `WAR_CANCEL_WAIT` = 24h (request to cancel)
  - `WAR_INACTIVE_WAIT` = 12h (after cancellation)
  - `WAR_REDECLARE_WAIT` = 48h
  - `MAX_DURATION` = 120h (5 days)

### GuardMembershipService
Three guard types with membership rules:
- **Royal Guard**: Range 0.6, 24h join delay
- **Elite Guard**: Range 0.75, 24h join delay
- **Black Guard**: 12h join/leave delays
- All require 48h into round

### ProtectionService
New-dominion safety period.
- `WAIT_PERIOD_DURATION_IN_HOURS` = 24
- `isUnderProtection()`, `canLeaveProtection()`, `getUnderProtectionHoursLeft()`

### InvasionService
Validates invasion rules (separate from InvadeActionService which executes them).
- `passes50PercentRule()`, `passes40PercentRule()`, `passes54RatioRule()`
- `hasEnoughBoats()`, `hasEnoughMorale()`, `hasEnoughDefense()`
- `getUnitReturnHoursForSlot()` (base 12h minus perks)

### SelectorService
Session-based dominion selection.
- `selectUserDominion()` / `getUserSelectedDominion()` / `unsetUserSelectedDominion()`
- `tryAutoSelectDominionForAuthUser()` - auto-selects if user has only one active dominion

### AIService
NPC and player automation.
- `executeAI()` - processes all AI dominions
- `performActions()` - executes tick-based instructions or NPC routines
- Supports player-defined automation via `ai_config` (tick-based action instructions)

### BountyService
Realm bounty board system.
- Max 40 bounties per dominion, max 15 observations
- Daily limits: 8 RP reward collections, 8 XP-only collections
- Rewards: 10 tech + 10 XP (if successful op), otherwise just XP

### HeroBattleService / HeroTournamentService
Turn-based 1v1 hero combat and multi-player tournaments.
- `DEFAULT_TIME_BANK` = 2h, `DEFAULT_STRATEGY` = 'balanced'
- Processed during hourly tick

### InfoOpService
Manages espionage/magic intelligence results.
- `hasActiveInfoOp(Realm, Dominion, type)` - checks for valid (non-stale) op
- Ops become stale after current hour, invalid after 12h

### RankingsService
Daily ranking snapshots by category (land, networth, conquered, explored, etc.).

## Top-Level Services (`src/Services/`)

### RealmAssignmentService (largest service)
Sophisticated pre-round realm assignment algorithm.
- Constants: `MAX_PACKS_PER_REALM` = 3, `MAX_PACKED_PLAYERS_PER_REALM` = 8, realms 8-14
- Uses nested classes: Player, PlaceholderPack, PlaceholderRealm
- Scoring: compatibility (favorability + playstyle balance) + rating deviation + size bonus
- Optimization: 50 iterations of random solo-player swapping

### NotificationService
Two-phase: queue then send.
- `queueNotification(type, data)` - buffers in memory
- `sendNotifications(Dominion, category)` - dispatches based on user settings
- Channels: WebNotification (in-game), HourlyEmailDigest, IrregularDominionEmail
- Categories: general, hourly_dominion, irregular_dominion, irregular_realm

### GameEventService
Town Crier event retrieval.
- `getTownCrier()` - paginated events filtered by realm/type
- Event types: invasion, war_declared/canceled, wonder_spawned/attacked/destroyed, raid_attacked, abandoned

### UserRatingService
Player skill ratings from round performance.
- Formula: 2000 * exp(-0.005 * (rank - 1)) + bonuses (land, ops, bounties, activity)
- Playstyle affinities: attacker, converter, explorer, ops
- Averages best 3 finishes + feedback score

### WonderService
Wonder lifecycle management.
- Tier2 (days 0-8), S-tier (day 9 only), Tier1 (day 10+)
- Max 6 concurrent wonders
- Sentient wonders attack top 3 damage-dealing realms

### RaidService
Raid completion and reward distribution.
- `processCompletedRaids()` - triggered during hourly tick
- `distributeRaidRewards()` - transactional reward distribution via RaidCalculator

### PackService
Player group management for realm assignment.
- Race limits: max 3 of same race per realm
- Alignment checking for non-mixed rounds

### DiscordService
Discord OAuth + guild/role management.
- Creates per-realm channels: general, top-op, ops-request, strategy-advice, war-room

### CouncilService / ForumService / MessageBoardService
Discussion forums at realm/round/global scope.
- Community flagging: content removed after 5 flags from 3 realms (forum) or 5 users (message board)

### JournalService
Post-round personal journals.
- 7-day creation window, 30-day edit window after round end

### ValorService
Awards valor for game achievements.
- Sources: largest_hit (5 + days*0.4), war_hit (10), wonder (amount*25), wonder_neutral (amount*10)
