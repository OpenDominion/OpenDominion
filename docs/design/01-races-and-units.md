# Races & Units

## Overview

Race selection is the most consequential decision a player makes before a round begins — it cannot be changed. Each of the 20 playable races defines a unique combination of passive economic bonuses and penalties, four military unit types with distinct offensive/defensive profiles and special abilities, and racial spells available only to that race. Together these form a playstyle identity that shapes how a dominion grows, fights, expands, and interacts with opponents throughout an entire round.

---

## Core Concepts

**Race** — The permanent identity of a dominion. Determines home land type, passive perks, available units, and racial spells.

**Alignment** — Races are grouped into Good and Evil factions. Alignment affects realm composition rules and potentially diplomacy.

**Home Land** — The terrain type a race starts with a bonus toward. One of: Plain, Mountain, Forest, Hill, Cavern, Swamp, Water.

**Difficulty** — Three independent axes (Attacker, Explorer, Converter) rated 1–3. Used to guide new players and set expectations. A separate "Overall" label (Beginner Friendly / Intermediate / Advanced) is derived from the combination.

**Race Perk** — A passive modifier that applies throughout the round. Can affect population caps, resource production, build costs, spy/wizard strength, and more.

**Unit** — One of four military unit types per race, each filling a role in offense, defense, or both.

**Unit Perk** — A special rule attached to a unit that modifies its behavior in combat, production, or utility.

---

## Race Alignment & Home Land

| Alignment | Races |
|---|---|
| Good | Human, Dwarf, Gnome, Halfling, Wood Elf, Firewalker, Merfolk, Sylvan |
| Evil | Orc, Goblin, Dark Elf, Nomad, Undead, Spirit, Demon, Icekin, Kobold, Lizardfolk, Lycanthrope, Troll, Vampire |

Home land types map loosely to terrain-based unit perks. Races living in Forest often have units that gain power from high forest land percentages; Mountain races benefit from mountain-scaling perks, and so on. This creates a feedback loop: playing to your race's strengths means also growing the land type your units benefit from.

---

## Race Perks

Race perks are always-on modifiers that apply to an entire dominion. They are defined in the race YAML and resolved by the game engine at calculation time. Perks can be benefits, drawbacks, or neutral flavor depending on context.

### Economic Perks

| Category | Examples |
|---|---|
| Population | Max population bonus/penalty, population growth rate, extra barren land housing |
| Food | Production bonus/penalty, consumption reduction |
| Lumber | Production bonus/penalty, decay rate |
| Ore | Production bonus |
| Gems | Production bonus, investment bonus |
| Mana | Production bonus |
| Platinum | Production bonus |
| Boats | Capacity bonus |

### Military & Tactical Perks

| Category | Examples |
|---|---|
| Offense / Defense | Flat percentage bonus to overall OP or DP |
| Spy Power | Global spy power bonus, split into offense or defense variants |
| Wizard Power | Global wizard power bonus, split into offense or defense variants |
| Spy Strength Recovery | Faster spy strength regeneration per tick |
| Wizard Strength Recovery | Faster wizard strength regeneration per tick |
| Immortal Wizards | Wizards do not die when operations fail |

### Construction & Economy Perks

| Category | Examples |
|---|---|
| Construction Cost | Percentage reduction to building costs |
| Investment Bonus | Bonus to castle improvement efficiency (general, gem-specific, or ore-specific) |
| Rezone Cost | Reduction to land rezoning cost |
| Explore Cost | Reduction to exploration platinum cost |

### Research Perks

| Category | Examples |
|---|---|
| Tech Production | Bonus to research points generated per tick |
| Tech Production (Invasion) | Bonus research points specifically earned from successful invasions |
| Tech Cost | Reduction in technology unlock cost |

### Hero Perks

| Category | Examples |
|---|---|
| Hero Bonus | Multiplier on hero ability effectiveness |
| Hero Experience | Bonus XP gain rate |

### Archetype Perks (Unit Infrastructure)

| Category | Examples |
|---|---|
| Barracks Housing | Extra units housed per barracks |
| Archmage Cost | Reduction in archmage training cost |
| Assassin Cost | Reduction in assassin training cost |

---

## Unit System

### The Four Slots

Every race has exactly four unit types occupying four ordered slots. Slots roughly correspond to tactical roles:

| Slot | Typical Role |
|---|---|
| 1 | Offensive specialist — high OP, little or no DP, cheapest elite |
| 2 | Defensive specialist — high DP, little or no OP, cheapest elite |
| 3 | Defensive elite — moderate-to-high DP, more expensive, often special perks |
| 4 | Offensive elite — moderate-to-high OP, more expensive, often special perks |

This is a convention, not a rule. Some races deviate significantly — hybrid elites occupy both offensive and defensive roles, and some slot 3 units are offensive-leaning.

### Unit Classification Types

Units carry a type label that determines their icon and broadly describes their combat role:

- `offensive_specialist` / `defensive_specialist`
- `offensive_elite` / `defensive_elite`
- `hybrid_specialist` / `hybrid_elite`

### Unit Costs

Units cost a combination of resources, always including platinum. Common secondary costs:

- **Ore** — Most common secondary cost, especially for armored or weapon-bearing units
- **Mana** — Used by magical races (Undead, Demon, Spirit, Vampire)
- **Lumber** — Uncommon; used by Orc, Sylvan
- **Gems** — Rare; used by Dark Elf's Spellblade

Some units explicitly require no boat (`need_boat: false`). These units can cross water without consuming boat capacity, important for naval races and invasions.

### Base Offensive and Defensive Power

Each unit has a base OP and DP value. These establish the unit's raw combat contribution before any multipliers. Typical archetypes:

- Pure attackers: high OP, zero DP
- Pure defenders: zero OP, high DP
- Hybrid units: moderate OP and DP
- Elites: higher absolute values than specialists, often with trade-offs

Draftees (untrained population on defense) each contribute 1 DP, providing a baseline defensive floor that scales with total population.

---

## Unit Perks

Unit perks are the primary source of mechanical diversity. A single perk can fundamentally change how a unit is used.

### Power Scaling Perks

These perks make a unit's OP or DP variable rather than fixed.

**Land-based scaling** — The unit gains OP or DP for each threshold of a specific land type owned. The bonus is capped at a maximum. Examples: a forest unit gaining DP per 20% forest owned, a mountain unit gaining DP per 20% mountain.

**Building-based scaling** — Same pattern but tied to building percentage. Example: gaining DP per threshold of a specific building type.

**Prestige-based scaling** — The unit gains OP per threshold of prestige earned. Rewards sustained aggressive play.

**Wizard ratio scaling** — The unit gains OP based on the dominion's wizard ratio (combined wizards and archmages relative to total land). Rewards heavy investment in the magic system.

**Staggered land range bonus** — The unit gains OP only when attacking dominions significantly larger than the attacker. A built-in bonus for underdog attacks.

**Versus-race bonus** — The unit gains OP or DP specifically against a particular enemy race. Hard-coded racial matchup asymmetry.

**Versus-building penalty** — The unit's power is reduced when the defender has high concentrations of a specific building type. Defenders can counter specific attacking unit types through smart building strategy.

**Pairing bonus** — The unit gains OP or DP when a minimum number of another unit type from the same race is present in the same engagement. Rewards building a complete army composition rather than spamming one unit type.

**Spell bonus** — The unit gains OP while a specific self-spell is active.

### Casualty Modifier Perks

These perks alter how many units die in combat.

**Percentage reduction/increase** — Modifies casualty rate by a percentage (offense, defense, or both). Units with this perk fight more sustainably but may cost more to train.

**Fixed casualties** — The unit always suffers a specific casualty rate regardless of other modifiers. Cannot be reduced by other effects. Used for high-power units that are inherently disposable.

**Immortal** — The unit cannot die. Immortal units always return after combat. Some variants are conditional (immortal when paired with another unit type, immortal when attacking within a size range, immortal against most races but not a specific one).

**Kills immortal** — The unit can eliminate enemy immortal units. A direct counter mechanic to immortal races.

**Rebirth** — The unit "dies" in combat but is reborn a set number of hours later. Effectively provides a delayed return rather than permanent loss.

**Reduce combat losses** — A passive bonus affecting the entire army's casualty calculation rather than just the individual unit.

### Conversion Perks

These perks allow units to generate new units as a byproduct of combat.

**Conversion** — The unit converts enemy peasants into new units at a fixed ratio. Each unit sent converts approximately one enemy peasant into a new unit of the specified type. Powerful for rapid military growth at the enemy's expense.

**Upgrade survivors** — A percentage of units that survive combat return as a more powerful unit type. Triggered when attacking a dominion of similar or larger size.

**Upgrade casualties** — Units killed in combat produce upgraded replacement units. Turns losses into a recruitment mechanism.

**Staggered conversion** — Units convert only when attacking a dominion above a size threshold.

**Garou-type conversion** — Units convert a fixed number of friendly units into an elite type per engagement.

### Resource Perks

Units can passively produce or plunder resources.

**Ore production** — The unit generates ore each hour it exists, in addition to training. Effectively reduces the cost of the ore it consumes by generating more.

**Plunder** — The unit steals a resource from the target dominion during a successful invasion. Applies to platinum, gems, or mana.

**Salvage** — The unit recovers a resource (lumber or ore) from buildings destroyed during combat.

### Naval Perks

**Sink boats (offense)** — The unit destroys enemy boats during a successful invasion, reducing the target's future invasion capacity.

**Sink boats (defense)** — The unit destroys attacker boats during a successful defense.

### Utility Perks

**Counts as spy** — The unit contributes a fraction of a spy's worth to the total spy count. Allows military units to supplement espionage capacity without training dedicated spies.

**Counts as wizard** — Same pattern for the wizard count. Some races use military units to supplement magical output.

**Charm spies** — Captured enemy spies join the dominion's spy pool rather than dying.

**Faster return** — The unit returns from invasions hours sooner than the standard return time.

**Barracks housing** — A unit itself provides additional housing for other units (per unit of this type).

---

## Power Calculation Overview

### Offensive Power

The total offensive power sent in an invasion is calculated in three stages:

1. **Raw OP** — Sum of all units' effective power (base + perks), plus pairing bonuses where applicable.
2. **OP Multiplier** — A composite multiplier drawn from: Gryphon Nest buildings, race perk bonuses, forge improvements (castle), active spells, technologies, wonder bonuses, war status, prestige, and hero abilities. Each component adds to the multiplier, subject to individual caps.
3. **Morale Modifier** — Morale penalizes OP when below maximum. At zero morale the penalty is significant; at full morale there is no penalty.

### Defensive Power

Calculated identically but with different multiplier sources:

1. **Raw DP** — Sum of all units' effective power (base + perks) plus all draftees at 1 DP each.
2. **DP Multiplier** — Drawn from: Guard Tower buildings, race perk bonuses, wall improvements (castle), active spells, technologies, and wonders. Enemy temples reduce the defender's effective DP as a distinct mechanic.
3. **Morale Modifier** — Same formula as offense.

### Key Interactions

- **Buildings and units reinforce each other.** Gryphon Nests multiply all offensive power; a race with an inherent offensive bonus benefits even more from Gryphon Nests. Guard Towers work the same way for defense.
- **Land composition can change unit effectiveness.** Land-scaling unit perks mean that rezoning land strategically can increase military power without training a single additional unit.
- **Pairing perks reward army diversity.** A player who only trains one unit type may miss out on substantial power that only activates when multiple unit types are present.
- **War status provides a mutual bonus.** Dominions in formal war receive an offensive bonus that scales with escalation, incentivizing active aggression rather than passive defense.

---

## Race Design Archetypes

While every race is unique, most fit roughly into strategic archetypes:

**Pure Attackers** — High inherent OP bonus, units with strong offensive perks, some casualty reduction. Lower defensive capacity. Examples: Gnome, Merfolk, Troll.

**Pure Defenders** — High inherent DP bonus, units with land-scaling DP, casualty reduction on defense. Limited offensive threat. Examples: Halfling, Icekin.

**Converters** — Units with conversion perks that generate new units from combat. Military power compounds over time through successful invasions. Examples: Lycanthrope (Garou), Dark Elf (Swordsman→Spellblade), Goblin.

**Explorers** — Races with land/cost bonuses that favor rapid expansion over combat. Not necessarily weak militarily but their economic perks reward growth. Examples: Nomad, Lizardfolk.

**Resource Specialists** — Races with dominant production bonuses in a specific resource, making them economic engines for their realm. Examples: Orc (lumber), Dwarf (ore), Firewalker (gems), Sylvan (lumber + food).

**Magical Races** — Races with immortal wizards or wizard-power bonuses. Their military units sometimes supplement the magic system. Examples: Dark Elf, Spirit, Vampire, Demon.

**Spy Races** — Races with spy-power bonuses. Their military units sometimes supplement espionage. Examples: Halfling (defensive spy), Lizardfolk (offensive spy).

---

## Interactions With Other Systems

- **[Land & Construction](02-land-and-construction.md)** — Home land type, land-scaling unit perks, and building multipliers are all race-specific.
- **[Population & Resources](03-population-and-resources.md)** — Race perks modify max population, food consumption, and resource output, setting the economic ceiling for every other system.
- **[Military](04-military.md)** — Unit OP/DP, casualty perks, conversion, and pairing mechanics are resolved during the invasion calculation.
- **[Magic](05-magic.md)** — Some units count as wizards; immortal wizard perks; racial-only spells provide combat multipliers to specific unit types.
- **[Espionage](06-espionage.md)** — Some units count as spies; charm spy perk.
- **[Heroes](07-heroes.md)** — Hero perks can modify OP/DP multipliers; hero experience rates are sometimes race-modified.
- **[Technology](08-technology.md)** — Tech bonuses apply on top of race and unit perks as late-game multipliers.
- **[Wonders](09-wonders.md)** — Wonder bonuses apply realm-wide and stack with all race and unit modifiers.

---

## Player Decision Space

**Pre-round:** Race selection defines the entire strategic identity. Players choose based on preferred playstyle (aggressive, defensive, economic, magical), experience level (difficulty ratings), and team coordination (realms may want a mix of resource specialists and military races).

**During the round:**
- Which unit types to train in what ratios (pure offensive, pure defensive, or mixed)
- Whether to invest in units with pairing bonuses (requiring balanced training) or simpler single-type armies
- How to grow land to maximize land-scaling unit perks (e.g., prioritizing forest if units scale with forest %)
- When to use conversion units aggressively to compound military growth
- Whether to rely on draftees for defense or train dedicated defensive units
- Resource allocation between units that have secondary costs vs platinum-only units

> **Note:** There is no in-round way to change race or retrain units into a different race's unit types. The commitment to a race's unit roster is permanent, which makes army composition decisions carry real long-term consequences.
