# Calculators Reference

## Design Principles

- **Pure computation**: Calculators never modify state - they only compute values
- **Raw + Multiplier pattern**: `getX() = getXRaw() * getXMultiplier()`
- **Constructor injection**: Dependencies injected via constructor (type-hinted properties)
- **Perk aggregation**: Most multipliers sum perks from race + spells + techs + wonders + hero + improvements
- **Tick mode**: `setForTick(true)` excludes next-hour queue resources (prevents double-counting)

## Dependency Graph

```
                    ┌─────────────────────┐
                    │   LandCalculator    │ ← Used by 13+ other calculators
                    │  (core dependency)  │
                    └────────┬────────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
  BuildingCalc         MilitaryCalc          PopulationCalc
        │              (8+ dependents)       (5+ dependents)
        │                    │                    │
        │              ┌─────┼─────┐              │
        │              │     │     │              │
        │          SpellCalc │  ImprovementCalc   │
        │          (6+ deps) │     │              │
        │              │     │     │              │
        │              HeroCalc                   │
        │              (8+ deps)                  │
        │                                         │
  ConstructionCalc  ExplorationCalc  TrainingCalc  ProductionCalc
  (Action)          (Action)         (Action)      (Production)
```

## Top-Level Calculators

### NetworthCalculator
Dominion and realm networth for rankings.
- Land = 20/acre, Buildings = 5 each, Spies/Wizards/etc = 5 each
- Unit networth = max(offense, defense) elite points * 2

### ValorCalculator
Round-end valor rankings.
- `FIXED_VALOR_LAND_RANK` = 6000, `LAND_TOTAL` = 3000, `LAND_CONQUERED` = 1500, `BOUNTIES` = 1500

### WonderCalculator
Wonder combat mechanics.
- `PRESTIGE_BASE_GAIN` = 25, `PRESTIGE_CONTRIBUTION_MULTIPLIER` = 50
- `MIN_SPAWN_POWER` = 150000, `MAX_SPAWN_POWER` = 500000
- Methods: `getCurrentPower()`, `getDamageDealt()`, `getPrestigeGainForDominion()`

### RaidCalculator
Raid scoring, objectives, rewards, leaderboards.
- `MAX_REALM_REWARD_RATIO` = 0.15, `MAX_PLAYER_REWARD_RATIO` = 0.15
- 40+ methods for tactic scoring, objective tracking, contributions, rewards

## Dominion Calculators (`src/Calculators/Dominion/`)

### LandCalculator (most depended-on)
- `getTotalLand()`, `getTotalBarrenLand()`, `getBarrenLandByLandType()`
- `getTotalLandIncoming()` (includes exploration + invasion queues)
- `getLandLostByLandType()` - distribution of land loss from invasion
- **Deps**: BuildingCalculator, BuildingHelper, LandHelper, QueueService

### MilitaryCalculator (most complex)
- **Power**: `getOffensivePower()`, `getDefensivePower()` (raw + multiplier)
- **Multiplier sources**: buildings, spells, techs, wonders, improvements, hero, morale, prestige
- **Ratios**: `getSpyRatio()`, `getWizardRatio()`
- **Boats**: `getBoatsNeeded()`, `getBoatCapacity()` (UNITS_PER_BOAT = 30, BOATS_PROTECTED_PER_DOCK = 2.25)
- **Land loss**: `getLandLossRatio()` (LAND_LOSS_MULTIPLIER = 0.75)
- **Deps**: GovernmentService, HeroCalc, ImprovementCalc, LandCalc, QueueService, SpellCalc

### PopulationCalculator
- Population: `getPopulation()`, `getMaxPopulation()`, `getPopulationMilitary()`
- Birth: `getPopulationBirth()` (raw * multiplier)
- Employment: `getEmploymentJobs()`, `getEmploymentPercentage()`
- **Deps**: BuildingHelper, HeroCalc, ImprovementCalc, LandCalc, MilitaryCalc, PrestigeCalc, QueueService, SpellCalc

### ProductionCalculator
Per-resource production with raw + multiplier:
- Platinum: peasant tax (2.7/peasant) + alchemy (45/building)
- Food: farms (80) + docks (40). Decay: 1%
- Lumber: lumberyards (50) + forest haven (25). Decay: 1%
- Mana: towers (25) + wizard guilds (5). Decay: 2%
- Ore: mines (60)
- Gems: diamond mines (15)
- Tech: schools (variable)
- Boats: docks (1 per 20 docks)
- Net change methods: `getFoodNetChange()`, `getLumberNetChange()`, `getManaNetChange()`

### SpellCalculator
- `getManaCost()`, `getStrengthCost()`, `canCast()`, `isOnCooldown()`
- `isSpellActive()`, `resolveSpellPerk()`

### OpsCalculator
Espionage/magic success rates and losses.
- `infoOperationSuccessChance()`, `theftOperationSuccessChance()`, `blackOperationSuccessChance()`
- `getSpyLosses()`, `getWizardLosses()`
- Resilience: `getResilienceGain()`, `getResilienceDecay()` (max 2000)
- Spell meters: `getSpellMeterGain()`, `getSpellMeterDecay()` (fireball, lightning_bolt)
- Mastery: `getMasteryChange()` (scales -500 to +500)

### CasualtiesCalculator
Unit losses from combat and starvation.
- `getOffensiveCasualtiesMultiplierForUnitSlot()`, `getDefensiveCasualtiesMultiplierForUnitSlot()`
- `getStarvationCasualtiesByUnitType()`
- Handles immortality, kills_immortal, conditional immortality perks

### PrestigeCalculator
Prestige gains/losses from invasions.
- `PRESTIGE_CAP` = 70, `PRESTIGE_RANGE_MULTIPLIER` = 200
- `PRESTIGE_LOSS_PERCENTAGE` = 5.0

### ImprovementCalculator
Castle improvement effectiveness.
- Types: science (20% cap), keep (25%), spires (60%), forges (30%), walls (30%), harbor (60%)
- Coefficients: science/keep (4000), spires/harbor (5000), forges/walls (7500)

### HeroCalculator
Hero XP and perk computation.
- `getExperienceGain()` from invasions, exploration, spying, magic
- `getHeroPerkMultiplier()`, `getPassiveBonus()`
- `INACTIVE_CLASS_PENALTY` = 0.5, `CLASS_CHANGE_COOLDOWN_HOURS` = 96

### RangeCalculator
Attack range for invasions.
- `MINIMUM_RANGE` = 0.4
- `isInRange()`, `getDominionRange()`, `getDominionsInRange()`
- Guard ranges: Royal = 0.6, Elite = 0.75

### BuildingCalculator
- `getTotalBuildings()`, `getTotalBuildingsForLandType()`
- `getBuildingTypesToDestroy()` - which buildings lost from invasion

### EspionageCalculator
- `canPerform()` - spy/wizard strength >= 30

## Action Calculators (`src/Calculators/Actions/`)

### ConstructionCalculator
Building costs with discounts.
- Platinum/lumber/ore/mana costs each with raw + multiplier
- `getDiscountedLandMultiplier()`, `getMaxAfford()`

### ExplorationCalculator
Exploration costs (complex scaling formula).
- `getPlatinumCost()` scales with total land
- `getDrafteeCost()` per acre
- `getMoraleDrop()` penalty

### TrainingCalculator
Unit training costs and limits.
- `getTrainingCostsPerUnit()` for all unit types
- `getMaxTrainable()` by resource availability

### RezoningCalculator
Land type conversion costs.

### BankingCalculator
Resource exchange rates with tech/wonder bonuses.

### TechCalculator
Technology research costs.
