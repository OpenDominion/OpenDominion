# Hero Combat Engine

Hero battles run on a self-contained, class-based engine in `src/HeroCombat/`. It handles
simultaneous turns, N-vs-N teams (co-op PvE, team PvP, NPC allies), duration and stack based
effects, ally targeting and AoE, cooldowns and charges, and deterministic replays.

The design borrows from Pokémon Showdown (simultaneous choice, priority ordering, ordered
event handlers, side/field effects, seeded replays), Unreal's Gameplay Ability System
(abilities + effects + modifiers, tag gating), and RPG Maker (state expiry timing).

## Module map

```
src/HeroCombat/
  Contracts/        Ability, Effect, AiStrategy, EnemyTemplate, Encounter, CombatantSpawner
  Engine/           Pure rules. No Eloquent, no Laravel.
    Battle.php          runtime facade passed to everything (state + services)
    BattleState.php     turn, seed, combatants, team/field effects, intents
    CombatantState.php  one combatant: base stats, health, abilities, effects, cooldowns, charges, queue
    TurnResolver.php    resolves one turn (see pipeline)
    BattleEngine.php    advance() loops turns while everyone is ready
    IntentDecider.php   forced → queued → AI
    ActionValidator.php cooldowns, charges, tag gating, canUse; queue projection
    ActionContext.php   what abilities receive: actor, targets, helpers
    Damage/             DamageRequest → DamageResolver → DamageResult (one pipeline for every hit)
    Effects/            EffectInstance, EffectManager (apply/stack/tick/remove), EffectDispatcher (ordered hooks)
    Stats/              Stat enum, Modifier, StatCalculator
    Targeting/          TargetRule enum, TargetResolver
    Events/             BattleLog, LogEntry, BattleEvent (structured), EventType
    Random/             RandomSource, SeededRandomSource
    Victory/            VictoryCondition, LastTeamStanding
  Content/          Everything game-specific. Add new content here.
    Abilities/          Attack, Defend, Focus, Counter, Recover, class abilities, Boss/ signature moves
    Effects/            Stances/, Passives/, Boss/ (telegraphs, phase cycles), buffs/debuffs
    Ai/WeightedStrategy presets (balanced, aggressive, defensive, pirate, …)
    Enemies/            EnemyTemplate classes
    Encounters/         Encounter classes (rosters, grants, scaling, victory rules)
    Loadouts/HeroClassLoadouts  class → abilities/passives (disabled by default: ENABLED = false)
  Registry/CombatRegistry  explicit key → definition maps
  Persistence/      BattleRepository (models ⇄ state, row lock), CombatantFactory, EloquentCombatantSpawner,
                    StateSnapshot, BattleReplayer
  Presentation/     BattlePresenter / BattleView (view model for Blade)
src/Providers/HeroCombatServiceProvider.php   registers all content (explicit lists)
src/Services/Dominion/HeroBattleService.php   app entry point: create battles, matchmaking, processTurn
src/Services/Dominion/HeroBattleOutcomeService.php   records, Elo, tournaments, raid contributions
```

The engine never touches models. `HeroBattleService` loads a `Battle` through
`BattleRepository`, runs `BattleEngine`, and persists the result inside one transaction holding a
`lockForUpdate` on the `hero_battles` row, so a tick and several players can't resolve the same
turn twice.

## Turn pipeline

```
processTurn(HeroBattle)
 └─ DB::transaction: lock row → load Battle
     └─ BattleEngine::advance — while not finished and every living human is ready:
         reseed RNG (seed + turn)
         TurnResolver::resolve
           1. TurnStart hooks (every effect instance)
           2. Intents     each living combatant: forcedIntent hook → queued action → AI strategy
           3. Declare     Ability::onDeclare for every intent (stances are applied here)
           4. Order       priority desc, then combatant id
           5. Resolve     per intent: resolve targets → Ability::resolve → start cooldown, spend charge
                          → AbilityUsed hooks → OwnerActionEnd expiry → process deaths
           6. TurnEnd hooks → tick TurnEnd durations → Encounter::onTurnEnd → process deaths
           7. VictoryCondition → finished / winning team, else turn++
     └─ persist state + log rows → if finished: HeroBattleOutcomeService::finalize
```

- **Simultaneous semantics.** A combatant downed earlier in the turn still acts. Anyone summoned mid-turn acts from the next turn.
- **Readiness.** NPCs are always ready. A human is ready when they have a queued action or are automated. `checkTime()` drains time banks and switches anyone at zero to automated.
- **Death processing** repeats until stable: `onDeath` runs on the dying combatant's own effects, then `onAnyDeath` on every other effect. A death trigger can kill, summon or revive.

## State and persistence

| Table | Engine field |
|---|---|
| `hero_battles.current_turn / seed / mode / encounter_key` | `BattleState` |
| `hero_battles.effects` | `{team: {n: [instances]}, field: [instances]}` |
| `hero_battles.winning_team` | source of truth for results; `winner_combatant_id` is kept as a representative (first human on the winning team) for older views |
| `hero_battles.initial_state` | `StateSnapshot` JSON taken at creation, for replays |
| `hero_combatants.team / template_key` | team number; enemy template key (null for heroes) |
| `hero_combatants.health … recover` | base stats (`health` = max health). Effects never mutate these |
| `hero_combatants.abilities` | active ability keys the combatant can choose |
| `hero_combatants.effects / cooldowns / charges` | effect instances; ability → turn it's usable again; ability → charges left |
| `hero_combatants.actions` | queued intents `[{ability, target}]` (max 6) |
| `hero_combatants.strategy` | AI strategy key (`CombatantState::$ai`) |
| `hero_battle_actions` | one row per resolved action or status line: `description` (rendered text) + `events` (structured) |

An effect instance is serialized as `{key, src, turns, stacks, data, turn, seq}`. `seq` is the
global apply order, which the dispatcher uses to break ties.

## Effects

Buffs, debuffs, statuses **and passives** are all `Effect` classes. Passives are effects of kind
`Innate`: permanent and not dispellable. Definitions are stateless singletons; per-application
state lives on the `EffectInstance` (stacks, remaining turns, `data`).

- **Scopes.** An effect lives on a combatant (`EffectManager::apply`), a team (`applyToTeam`) or the battlefield (`applyToField`). Team and field instances apply to whoever `Effect::appliesTo()` accepts. The default excludes ids listed in `data.exclude`.
- **Stacking.** `Refresh` resets the duration and merges data. `Stack` adds stacks up to `maxStacks()`, which may depend on the owner (Focused stacks without limit under Channeling). `Replace` swaps in a new instance. `Independent` keeps every application.
- **Expiry timing.** `TurnEnd` (default), `OwnerActionEnd`, `OnDamageTaken`, or `OnConsume` (removed only explicitly). An effect applied *during* end-of-turn ticking is not ticked in that same pass, which is how Freezing hands over to Frozen for exactly the next turn.
- **Tags** (`CombatTag`). Effects grant tags. Abilities declare `blockedByTags()` (default: Stunned) and `requiredTags()`. Effects can declare `immuneToTags()`. Cleanse removes `Curse`-tagged effects. Stances are queried by tag (`Defending`, `Countering`).
- **Modifiers.** `effective = round((base + Σflat) × (1 + Σpercent))`, then overrides (e.g. Frozen → 0), clamped at 0. A modifier must not compute other *effective* stats inside `modifiers()`, because that recurses. Use `baseStat()` (see Mending).

### Hook reference (all no-op in `AbstractEffect`)

| Hook | Runs for | Typical use |
|---|---|---|
| `onApply` / `onExpire` / `onRemove` | the instance | setup; hand-offs (Freezing→Frozen); `onRemove` covers dispels too |
| `onTurnStart` / `onTurnEnd` | every instance in the battle | telegraph rolls, phase changes, regen/DoT |
| `forcedIntent` | the acting combatant's effects | telegraphed moves, charged attacks |
| `beforeDamageDealt` | attacker's effects | add bonus damage / defend modifier (Crushing Blow) |
| `redirectDamage` | target's effects | Cover |
| `onEvaded` | target's effects | Elusive sets `evadeMultiplier = 0` |
| `beforeDamageTaken` | target's effects | Shield absorbs (priority 10) |
| `onLethalDamage` → bool | target's effects | Hardiness (priority -10, after shields) |
| `afterDamageTaken` / `afterDamageDealt` | target / attacker | Lifesteal, Frostbite stacks |
| `onAbilityUsed` | actor's effects | consume Focused after an Attack-tagged ability |
| `onDeath` | the dead combatant's own effects | PowerSource, PhaseCycle cleanup |
| `onAnyDeath` | every other effect | soul tribute / harvest style mechanics |

**Ordering.** `EffectDispatcher` sorts handlers by `handlerPriority($hook)` (high first), then
by apply sequence. "Runs for X's effects" includes the team and field instances that apply to X.

## Damage pipeline (`DamageResolver::resolve`)

1. `beforeDamageDealt` (attacker), then `redirectDamage` (target)
2. Raw damage: `flatDamage × multiplier × hits` if flat, else `max(0, power − defense) × multiplier × hits`, where
   - power = Attack (+ Counter if this is a counter-attack, otherwise + Focus × stacks if Focused) + bonusDamage
   - defense = effective Defense (Defending doubles it via a +100% modifier) + `defendModifier` if Defending, or 0 with `ignoreDefense`
3. Evasion (if `canEvade`): roll `int(0,100) < Evasion` → ×0.5, then `onEvaded` hooks
4. `beforeDamageTaken` (shields; skipped by `ignoreShield`)
5. Lethal save via `onLethalDamage` unless `bypassLethalSave`
6. Apply → `OnDamageTaken` expiry → `afterDamageTaken` / `afterDamageDealt`
7. Counter-attack if the target holds `Countering` and the hit is counterable: attack + counter vs defense, no evasion, not shielded, not itself counterable, × hits

`ActionContext::attack()` builds a standard request. `ActionContext::flatDamage()` builds fixed
damage that ignores defense, evasion and counters (boss moves).

## Abilities, AI, targeting

- **Abilities** implement `key/name/description/resolve`. `AbstractAbility` supplies defaults: SingleEnemy, priority 0, no cooldown, unlimited charges. `cooldown()` returns the turns to wait after use; 1 means "not twice in a row" (Focus, Counter, Recover, class abilities). Both `cooldown()` and `maxCharges()` receive the actor, so they can vary.
- **Queue validation** (`ActionValidator::canQueue`) projects cooldowns and charges forward through the actions already queued. At resolution, an invalid queued action falls back to AI.
- **Target rules**: Self, SingleEnemy, SingleAlly, SingleAny, AllEnemies, AllAllies, AllOthers, RandomEnemy. A dead single-enemy target is replaced with a random living enemy; dead ally targets fizzle. `Provoked` forces single-enemy targeting onto the provoker.
- **AI**: `WeightedStrategy` picks a usable ability by weight. It drops Fortify while shielded, drops Recover when it would overheal, and doubles Recover at ≤40 HP. Ally-targeted abilities pick the most wounded ally. Player-selectable strategies are flagged `playerSelectable`.
- **Pass** is chosen when nothing is usable (e.g. while stunned).

## Encounters and enemies

An `EnemyTemplate` gives stats, active abilities (`AbstractEnemy` adds the five basics), innate
effects with data, and an AI key. An `Encounter` gives a roster (template, display name, stat and
effect overrides) built from an `EncounterContext` (raid `priorWins`, the leader's stats),
optional `playerGrants()` (class-conditional abilities), a `VictoryCondition`, an `onTurnEnd`
script hook, and an optional victory line. Keys must match the `encounter` attribute raids use on
their `RaidObjectiveTactic` rows.

Ported so far: `default` (Evil Twin), `admiral_varos`, `heart_of_ice`, `lich_king`. To see the
rest of the original definitions, use `src/Helpers/HeroEncounterHelper.php` and
`HeroHelper::getCombatActions()` in git history before the `feature/hero-combat-v2` branch.

## Recipes

**Add an ability**
1. Create `Content/Abilities/MyAbility.php` extending `AbstractAbility` (or `AbstractAttack` for weapon hits; override `damageOptions()`).
2. Implement `resolve(ActionContext $context)` using `$context->attack()`, `flatDamage()`, `heal()`, `applyEffect()`, `summon()`, `chose($target, 'defend')` and `say('messageKey', [...])`.
3. Add it to `HeroCombatServiceProvider::ABILITIES`, then grant it through a template, a loadout or `Encounter::playerGrants()`.

**Add a buff or debuff**: extend `AbstractEffect`. Set `kind()`, `stacking()`, `defaultDuration()` and `tags()`, and return modifiers or override hooks. Register it in `EFFECTS` and apply it with `$context->applyEffect($target, 'key', $turns, $stacks, $data)`.

**Add a passive**: extend `AbstractPassive` (or `AbstractStatPassive` for flat stat bonuses, optionally gated to low health). Give it to an enemy through `EnemyTemplate::effects()`, or to a class through `HeroClassLoadouts`.

**Add a telegraphed boss move set**: extend `Effects/Boss/AbstractTelegraph`, list `moves()` and `tells()` (tells must not name the counter-play), and add the move abilities extending `Abilities/Boss/AbstractBossMove`. Put the telegraph in the boss template's `effects()`.

**Add a phase-cycling boss**: extend `Effects/Boss/AbstractPhaseCycle` with `phases()` (self effects plus an ally aura per phase) and `turnsPerPhase()`.

**Add an enemy or encounter**: create the classes in `Content/Enemies` and `Content/Encounters` and add them to `ENEMIES` and `ENCOUNTERS`. Practice battles list every registered encounter automatically.

**Add an engine-level hook**: add a case to `Effects/Hook` (value = method name), add the method to `Contracts/Effect` and `AbstractEffect`, and dispatch it from the engine through `EffectDispatcher`.

## Testing

- Pure engine tests live in `tests/Unit/HeroCombat` (no DB). `Support/BattleBuilder` builds in-memory battles (`->hero()`, `->npc()`, `->withRoster()` for an encounter). `Support/ScriptedRandomSource` makes randomness controllable: evasion never procs and chances succeed unless queued. `BuildsBattles` provides `queue()`, `declare()`, `perform()` and `resolveTurn()`.
- DB flow tests: `tests/Feature/HeroCombat/HeroBattleFlowTest.php`. Pages: `tests/Feature/Http/Dominion/HeroBattlePagesTest.php`. Varos: `tests/Unit/Services/Dominion/HeroBattleServiceTest.php`.
- `php artisan test --compact tests/Unit/HeroCombat`

## Replays

Every battle stores a seed and an initial snapshot, and each action row records its intent's
source (`events[0]`: `selected`, with `source` = queue|ai|forced). `BattleReplayer` restores the
snapshot, replays queued intents (AI and forced intents are deterministic per seed and turn),
gives summons their original ids, and diffs the log turn by turn:

```
php artisan hero-combat:replay {battle}
```

## Old → new mapping

| Old (HeroBattleService / HeroCalculator) | New |
|---|---|
| `has_focus`, channeling `focus +=` | `focused` effect (stacks with Channeling), consumed by Attack-tagged abilities |
| `shield` column, Fortify top-up | `shield` effect with `data.pool` (Replace) |
| `current_action == defend/counter/recover` | `defending`/`countering`/`recovering` stances applied at declare; bosses check `chose()` |
| `status.frostbite` | `frostbitten` stacking debuff |
| `frozen_pending` → `frozen` | `freezing` → (`onExpire`) → `frozen` |
| `increment('attack', 1)` etc. | `forged` / `analyzed` / `outmaneuvered` stacking stat shifts |
| `telegraphed_move` / `telegraphed_order` | `AbstractTelegraph` (`snow_witch_curse`, `admirals_orders`) |
| Tome `base_abilities` merging | `AbstractPhaseCycle` with team auras (`tome_of_power`) |
| `power_source` by display name | `power_source` by template key → `severed` debuff |
| `hero_id === null` means enemy | `team` |
| `winner_combatant_id` | `winning_team` (+ representative id) |
| `limited` actions | `cooldown() = 1` |
| sortBy('fortify') | `priority()` (Fortify = 10) |

Deliberate rebalances: destroying the Tome also removes its active chapter aura from the Lich
King. Counter-attacks no longer inherit the triggering ability's bonus damage. Mending adds base
focus (not effective focus) to Recover.
