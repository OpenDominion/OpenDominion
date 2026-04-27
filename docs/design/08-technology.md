# Technology

## Overview

Technology is the long-term multiplier layer of OpenDominion. Dominions spend research points — generated exclusively by School buildings — to unlock permanent bonuses across every game system. The tech tree branches through prerequisites, rewarding early investment in a clear strategic direction while still allowing pivots through the OR-based prerequisite logic. Unlike spells or unit perks, tech bonuses never expire. Each unlock is a permanent improvement to the dominion for the rest of the round.

---

## Core Concepts

**Research Points (RP)** — The currency for unlocking technologies. Generated hourly by School buildings. Stored in `resource_tech`; no decay.

**Tech Tree** — A structured graph of available technologies. Two versions exist (Classic/v1 and Current/v2), selected per round configuration. Each has its own prerequisite graph.

**Prerequisite (OR logic)** — A technology lists one or more prerequisite techs. The dominion only needs **one** of them unlocked to qualify. This enables multiple valid paths to the same advanced technology.

**Cost Scaling** — Each tech costs more based on how much land the dominion has reached and how many techs are already unlocked. Early techs hit the minimum floor; later techs scale upward.

**Perk Stacking** — If two techs provide the same perk type, both values are added. There is no stacking cap — every tech's contribution counts fully.

---

## Research Point Generation

Schools are the only source of research points. The formula applies a diminishing return based on the school density:

- Schools beyond 50% of total land produce nothing — they are capped.
- The production per school decreases as the proportion of land dedicated to Schools increases.
- The practical result: early School investment is highly efficient; stacking Schools beyond a threshold yields progressively less.

Final production is multiplied by race perk bonuses, hero bonuses (certain hero perks), and wonder bonuses.

Because Schools occupy Cavern land that could otherwise hold Diamond Mines, every School placed represents a gem production opportunity foregone. The opportunity cost is real throughout the round.

---

## Tech Cost

Tech cost scales with two factors:
1. **Highest land ever reached** — larger dominions pay more per tech.
2. **Number of techs already unlocked** — each successive tech costs more than the last.

A minimum floor prevents early techs from being trivially cheap. The scaling ensures that a dominion cannot simply rush to unlock everything without sustained School investment.

**Cost reductions** are available through race perks and certain hero perks. These apply as multipliers to the formula, not as flat discounts.

---

## Prerequisite Logic

Each technology in the tree lists zero or more prerequisites. The dominion qualifies for a tech if it has unlocked **at least one** of the listed prerequisites. This OR-based system means:

- Technologies with multiple prerequisites can be reached through different paths.
- Specializing along one branch still opens advanced techs that nominally list multiple entry points.
- Players are not required to research broadly — depth is rewarded.

Technologies with no prerequisites are always available from the start and form the entry points of the tree.

---

## Tech Tree Versions

### Classic Tree (v1)

A simpler linear structure organized into tiers. Tier 1 techs have no prerequisites. Tier 2 requires completing one or two Tier 1 techs. Tier 3 requires Tier 2 completion. Around 42 total technologies.

### Current Tree (v2)

A complex graph with techs arranged on a two-dimensional grid. Techs have x/y coordinates and form dense prerequisite networks where a single tech may list three or four possible entry paths. Around 53 total technologies arranged across approximately 11 rows of increasing depth.

The v2 tree is the active default for current rounds.

---

## Technology Categories and Perks

Technologies provide bonuses across every game system. Perk types are additive across all unlocked techs.

### Military

| Perk | Effect |
|---|---|
| Offense bonus | Increases overall OP multiplier |
| Defense bonus | Increases overall DP multiplier |
| Military training cost | Reduces platinum/resource cost per unit |
| Casualty reduction (global) | Reduces losses from all combat |
| Casualty reduction (offense) | Reduces losses only when attacking |
| Casualty reduction (defense) | Reduces losses only when defending |
| Prestige gains | Increases prestige earned per qualifying hit |
| Boat capacity | Increases units per boat |
| Barracks housing | Increases units housed per Barracks |
| Guard tax modifier | Adjusts the platinum tax from Guard membership |

### Construction and Land

| Perk | Effect |
|---|---|
| Construction platinum cost | Reduces building construction cost |
| Construction lumber cost | Reduces building lumber cost |
| Rezone cost | Reduces rezoning platinum cost |
| Destruction discount | Converted acres when demolishing buildings |
| Destruction refund | Resource recovery when demolishing |
| Explore platinum cost | Reduces exploration cost per acre |
| Explore draftee cost | Reduces draftee cost per explored acre |
| Max population | Increases population capacity |
| Population growth | Increases birth rate |
| Extra barren population | More people housed per barren acre |

### Resource Production

| Perk | Effect |
|---|---|
| Platinum production | Multiplier on all platinum output |
| Food production | Multiplier on all food output |
| Food production (docks) | Multiplier specifically on dock food |
| Food production (prestige) | Scales food bonus from prestige |
| Food consumption | Reduces food consumed per tick |
| Food decay | Reduces food decay rate |
| Lumber production | Multiplier on all lumber output |
| Lumber decay | Reduces lumber decay rate |
| Ore production | Multiplier on all ore output |
| Gem production | Multiplier on all gem output |
| Mana production | Multiplier on all mana output |
| Mana production (raw) | Flat mana per Tower per tick |
| Wartime mana production | Additional flat mana per Tower during each active war |
| Mana decay | Reduces mana decay rate |
| Boat production | Multiplier on boat generation rate |
| Exchange bonus | Improves resource exchange rates |
| Investment bonus (harbor) | Increases harbor castle improvement efficiency |
| Investment bonus (spires) | Increases spires castle improvement efficiency |

### Espionage

| Perk | Effect |
|---|---|
| Spy power | Increases spy ratio multiplier |
| Spy power (defense) | Increases defensive spy ratio only |
| Spy losses | Reduces spy losses on failed operations |
| Spy strength recovery | Additional spy strength per tick |
| Spy training cost | Reduces spy training cost |
| Assassin training cost | Reduces assassin training cost |
| Theft gains | Increases resources stolen per successful theft |
| Theft losses (target) | Reduces theft losses suffered as defender |
| Fool's Gold cost | Reduces Fool's Gold spell mana cost |
| Improved Fool's Gold | Extends Fool's Gold protection to ore, lumber, and mana |
| Enemy assassinate draftees damage | Reduces draftees lost to assassination ops |
| Enemy assassinate wizards damage | Reduces wizards lost to assassination ops |
| Enemy disband spies damage | Reduces spies lost to Disband Spies spell |

### Magic

| Perk | Effect |
|---|---|
| Wizard power | Increases wizard ratio multiplier |
| Wizard strength recovery | Additional wizard strength per tick |
| Wizard training cost | Reduces wizard training cost |
| Archmage training cost | Reduces archmage training cost |
| Spell cost | Reduces mana cost for all spells |
| Self spell cost | Reduces mana cost for self spells specifically |
| Racial spell cost | Reduces mana cost for racial self spells |
| Enemy fireball damage | Reduces incoming Fireball damage |
| Enemy lightning bolt damage | Reduces incoming Lightning Bolt damage |
| Enemy burning duration | Reduces duration of Burning status effect |
| Enemy spell duration | Reduces duration of hostile spells applied to the dominion |
| Wonder damage | Increases damage dealt when attacking wonders |
| Raid attack damage | Increases raid damage output |

---

## Notable Technologies (v2)

A selection of technologies that have meaningful strategic implications:

**Tributary System** — Dramatically increases food production scaled by prestige. Makes high-prestige dominions significantly more food self-sufficient, rewarding sustained aggressive play.

**Battle Tactics** — Late-game tech providing flat casualty reduction. Because it sits at the apex of the v2 tree, reaching it signals deep tech investment.

**Menace** — Increases raw mana per Tower plus wartime bonus mana. Rewards aggressive realm war engagement specifically through magic infrastructure synergy.

**Improved Fool's Gold** — Extends the Fool's Gold spell's protection beyond platinum to cover ore, lumber, and mana. A qualitative unlock rather than a percentage modifier — it changes the character of the spell.

**Ross' Benevolence** — Reduces the platinum tax paid to the Royal Guard. Primarily relevant for dominions that join the guard and want to offset the tax cost.

**Urg Smash Technique** — Named tech providing a large destruction discount alongside lumber production. Enables a razing-heavy playstyle where destroying buildings generates free rebuild credits.

**Public Baths** — Provides a large population growth bonus. Combined with Temple buildings and racial growth perks, allows population to grow very rapidly.

**Bunk Beds** — Adds flat barracks housing capacity. Allows more military units to be housed without additional Barracks construction — effectively expanding military capacity for free.

**Trick of the Light** — Qualitatively upgrades Fool's Gold (if the tech is also present). Requires navigating through gem production techs to reach.

**Centralized Intelligence** — Reduces spy training cost significantly. Deep in the espionage branch; rewards a spy-heavy strategy with training efficiency.

---

## Strategic Pathing

The v2 tech tree's OR prerequisites mean there is no single correct path. Common strategic priorities:

**Economic path** — Early food and platinum production techs, moving toward resource decay reduction and exchange bonuses. Maximizes economic output and sustainability.

**Military path** — Casualty reduction and offense/defense bonuses. Often combined with prestige gain techs to compound invasion returns.

**Espionage path** — Spy power, spy loss reduction, theft gains. Converges toward Centralized Intelligence and Shadow Academy for training cost efficiency.

**Magic path** — Wizard power and mana production. Can stack with enemy spell damage reduction to create a resilient defensive magical posture.

**Hybrid paths** — Because prerequisites are OR-based, a player can enter the advanced portion of the tree through one branch and then pick up perks from an adjacent branch at the same depth level.

The density of the v2 tree means most dominions will unlock 10–25 technologies over a round rather than the full tree. Choosing which branch to prioritize — and when to detour for a high-value side perk — defines the tech strategy.

---

## School Investment Trade-offs

The core economic tension of the tech system:

- Schools occupy Cavern land. The only alternative Cavern buildings are Diamond Mines.
- Schools produce research points with diminishing returns past 50% of land.
- Building more Homes frees up more land for Schools but reduces productive buildings elsewhere.

A dominion that ignores Schools entirely falls behind in tech permanently — compound bonuses accumulate over the round. A dominion that over-invests in Schools wastes Cavern land that could be producing gems and may have insufficient research points to spend efficiently.

The practical optimum for most dominions is a moderate School investment that sustains a tech unlock every several days, adjusting upward if the race has a tech production bonus perk.

---

## Interactions With Other Systems

- **[Races & Units](01-races-and-units.md)** — Race perks modify tech production rate and tech cost. Some races (Gnome) have native tech production bonuses. Some unit perks interact with techs (e.g., tech improving specific unit casualty outcomes).
- **[Land & Construction](02-land-and-construction.md)** — Schools occupy Cavern land; Diamond Mines are the alternative. Tech perks reduce construction and rezone costs. Destruction discount/refund techs change demolition economics.
- **[Population & Resources](03-population-and-resources.md)** — Tech perks modify every production resource (food, platinum, lumber, ore, mana, gems, boats). Decay reduction techs are particularly impactful for food, lumber, and mana.
- **[Military](04-military.md)** — Offense/defense bonuses, casualty reduction, training cost reduction, and boat capacity all feed directly into invasion mechanics.
- **[Magic](05-magic.md)** — Wizard power, spell cost, enemy spell duration reduction, and damage resistance perks all layer onto the magic system's success and damage calculations.
- **[Espionage](06-espionage.md)** — Spy power, spy loss reduction, theft gains, and training cost perks directly improve operation success and economics.
- **[Heroes](07-heroes.md)** — Hero tech production perks compound School output. Scholar advanced class requires prior tech investment and provides its own tech bonus directive.
- **[Wonders](09-wonders.md)** — Wonders can provide tech production bonuses. Some wonder perks overlap with tech perk types, stacking additively.

---

## Player Decision Space

**Which branch to enter first** — The foundational row contains several strong entry techs. Choosing which one to unlock first commits the player toward one branch of the v2 tree for the next several unlocks. Military players often start with casualty reduction or offense entries; economic players start with food or production entries.

**How many Schools to build** — More Schools accelerate tech unlocks but at the cost of Cavern land. The correct ratio depends on how tech-dependent the strategy is. Races with tech production bonuses can afford fewer Schools for the same output.

**When to diverge** — The OR prerequisite system allows a player to eventually branch out from their primary path. Timing this correctly — branching early enough to benefit from cross-path perks before the round ends — is a meaningful skill.

**Prioritizing perk synergies** — Some tech combinations are greater than their individual parts. Unlocking both wartime mana production perks amplifies the value of every Tower during war. Unlocking multiple casualty reduction techs compounds into meaningful unit preservation. Identifying these synergies and pathing toward them is the depth of the system.

> **Note:** Tech bonuses are the only permanent, non-decaying multipliers in the game (beyond castle improvements). A dominion that unlocks strong early techs maintains that advantage for the entire round, while a dominion that neglects techs cannot recover the missed growth. The compounding nature of research point production — higher output means more frequent unlocks which means more multiplier bonuses — makes early School investment disproportionately valuable.
