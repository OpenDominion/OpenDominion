# Data Model Reference

## Entity Relationship Overview

```
User ──has many──→ Dominion ──belongs to──→ Round
                   Dominion ──belongs to──→ Realm ──belongs to──→ Round
                   Dominion ──belongs to──→ Race ──has many──→ Unit
                   Dominion ──belongs to──→ Pack
                   Dominion ──has one────→ Hero ──has many──→ HeroUpgrade
                   Dominion ──has many──→ Dominion\Queue
                   Dominion ──has one────→ Dominion\Tick (next-hour precalculation)
                   Dominion ──has many──→ Dominion\History (delta audit trail)
                   Dominion ──btm──────→ Spell (via dominion_spells pivot: duration, cast_by)
                   Dominion ──btm──────→ Tech (via dominion_techs pivot: source morph)
```

## Base Classes

- **AbstractModel** - Sets `$guarded = ['id', 'created_at', 'updated_at']`
- **AbstractPivot** - Same guarding for pivot models

## Core Models

### User (`users`)
- **Traits**: Authenticatable, Authorizable, HasRoles (Spatie), Notifiable
- **Casts**: settings (array), affinities (array)
- **Relations**: dominions, activities, identities, origins, discordUser, feedback, endorsements, achievements
- **Key methods**: `isStaff()`, `isOnline()` (5min), `isInactive()` (72h), `getSetting()`, `getAffinity()`

### Round (`rounds`)
- **Relations**: dominions (through Realm), realms, packs, raids, tournaments, wonders, gameEvents, wars (through Realm), league
- **Scopes**: `active()`, `activeSoon()`, `readyForAssignment()`
- **Key methods**: `graveyard()` (realm #0), `hasStarted()`, `hasEnded()`, `getTick()`, `daysInRound()`, `hasOffensiveActionsDisabled()`

### Realm (`realms`)
- **Relations**: dominions, round, monarch/general/spymaster/magister/mage/jester (all Dominion), packs, roundWonders, warsIncoming, warsOutgoing, councilThreads, history
- **Scopes**: `active()` (number != 0, excludes graveyard)
- **Key methods**: `totalPackSize()`, `hasWonder()`, `getSetting()`
- **History**: Custom `save()` records deltas via RealmHistoryService

### Dominion (`dominions`) - Main game entity
- **Relations**: realm, round, race, user, pack, hero, heroes, spells (btm), techs (btm), queues, tick, history, journals, rankings, raidContributions, councilThreads/Posts, sourceEvents/targetEvents, infoOps
- **Scopes**: `active()`, `bot()` (user_id null), `human()`
- **Casts**: settings (array), ai_config (array)
- **Key methods**:
  - State: `isLocked()`, `isAbandoned()`, `isActive()`, `isBuildingPhase()`
  - Court: `isMonarch()`, `isGeneral()`, `isSpymaster()`, `isMagister()`, `isMage()`, `isJester()`, `isCourtMember()`
  - Perks: `getSpellPerkValue()`, `getTechPerkValue()`, `getWonderPerkValue()` (aggregate active perks)
  - Abandonment: `requestAbandonment()`, `resetAbandonment()`, `cancelAbandonment()`
- **History**: Custom `save()` records deltas via HistoryService

### Race (`races`)
- **Relations**: dominions, units (ordered by slot), perks (btm with RacePerkType)
- **Scopes**: `active()` (playable = true)
- **Key methods**: `getPerkValue()`, `getPerkMultiplier()`, `getUnitPerkValueForUnitSlot()`

### Unit (`units`)
- **Relations**: race, perks (btm with UnitPerkType)
- **Fields**: slot (1-4), name, cost_platinum/ore/mana/lumber/gems, power_offense/defense, type
- **Key method**: `getPerkValue()`

## Perk System Models

All follow the same pattern: Entity ←btm→ PerkType via Perk pivot (with `value` column).

| Entity | Perk Pivot | PerkType Table | Example Perks |
|--------|-----------|----------------|---------------|
| Race | race_perks | race_perk_types | food_production, defense, spy_power |
| Unit | unit_perks | unit_perk_types | kills_immortal, immortal_vs_land_range |
| Spell | spell_perks | spell_perk_types | destroy_peasants, food_production |
| Tech | tech_perks | tech_perk_types | platinum_production, max_population |
| Wonder | wonder_perks | wonder_perk_types | prestige, spy_losses |

Hero upgrades use embedded `HeroUpgradePerk` (key/value) instead of the btm pattern.

## Game State Models

### Dominion\Tick (`dominion_tick`)
- Pre-calculated snapshot of next hour's changes (resources, military, land, etc.)
- Created/updated by `TickService::precalculateTick()`
- Applied during `TickService::performTick()` via batch SQL updates
- **Casts**: starvation_casualties (array), expiring_spells (array)

### Dominion\Queue (`dominion_queue`)
- Fields: dominion_id, source, resource, hours, amount
- Sources: construction, exploration, training, invasion, operations
- Hours decrement each tick; at 0, resources apply to dominion

### Dominion\History (`dominion_history`)
- **Casts**: delta (array)
- Records changed attributes as delta (new - old)
- Tracks IP and device for security audit

### GameEvent (`game_events`)
- **UUID primary key** (auto-generated)
- **Polymorphic**: source/target via morphTo() (Dominion, RoundWonder, Realm, RealmWar)
- **Types**: invasion, war_declared, war_canceled, wonder_spawned, wonder_attacked, wonder_destroyed, raid_attacked, abandoned

### InfoOp (`info_ops`)
- Espionage/magic intel results cached per source realm + target dominion
- **Key methods**: `isStale()` (older than current hour), `isInvalid()` (older than 12h)
- **Dispatches**: InfoOpCreatingEvent (marks previous ops as non-latest)

## Hero & Combat Models

### Hero (`heroes`)
- **Relations**: dominion, battles (btm HeroBattle), upgrades (btm HeroUpgrade), combatants, queue
- **Casts**: class_data (array)
- **Key methods**: `combatActionRequired()`, `getPerkValue()`, `getPerkMultiplier()`

### HeroBattle (`hero_battles`)
- **Relations**: round, combatants, actions, winner (HeroCombatant), tournaments (btm), tactic
- **Scopes**: `active()` (finished = false)

### HeroTournament (`hero_tournaments`)
- **Relations**: round, winner (Dominion), battles (btm with round_number), participants

## Realm & War Models

### RealmWar (`realm_wars`)
- **Relations**: sourceRealm, targetRealm
- **Scopes**: `active()` (inactive_at null or future)
- War lifecycle: declared → 24h wait → active → cancel request → 24h wait → inactive → 12h cooldown

### RoundWonder (`round_wonders`)
- **Relations**: round, realm (current holder), wonder, damage records
- Tracks power and damage from all realms/dominions

### Pack (`packs`)
- **Relations**: creatorDominion, dominions, realm, round, users (through Dominion)
- **Key methods**: `isFull()`, `isClosed()`, `remainingSlots()`

## Raid Models

### Raid → RaidObjective → RaidObjectiveTactic → RaidContribution
- Time-bound group objectives with multiple tactic types (hero/investment/exploration/espionage/magic/invasion)
- Contributions tracked per dominion per objective per tactic

## Communication Models

All use **SoftDeletes** and have `flagged_by` (array cast) for community moderation:
- **Council\Thread/Post** - Realm-only discussion (relations: dominion, realm)
- **Forum\Thread/Post** - Round-wide discussion (relations: dominion, round)
- **MessageBoard\Thread/Post** - Global discussion (relations: user, category)

## User Tracking Models

- **UserActivity** - Login/logout/action events with IP, device, status
- **UserIdentity** - Browser fingerprints with count
- **UserOrigin** - IP addresses per user with geolocation lookup
- **UserFeedback** - Endorsements between users per round
- **DiscordUser** - Discord OAuth identity link
- **DailyRanking** - Daily snapshots of dominion rankings by type
- **Valor** - Prestige-like metric awarded for game actions
