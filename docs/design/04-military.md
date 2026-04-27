# Military

## Overview

The military system is the primary mechanism through which dominions compete for land, resources, and prestige. Players train units, invade enemy dominions to seize land, defend against incoming attacks, and manage the costs and consequences of combat. Every invasion is a single binary outcome — success or failure — resolved by comparing the attacker's offensive power against the defender's defensive power. The surrounding mechanics (range limits, morale requirements, casualty calculations, prestige gains, boat logistics) create the strategic depth around that core comparison.

---

## Core Concepts

**Offensive Power (OP)** — Total combat strength the attacker brings to an invasion.

**Defensive Power (DP)** — Total combat strength the defender presents at home.

**Morale** — A 0–100 score that applies a penalty to both OP and DP at low values. Required to reach a minimum before invading.

**Prestige** — Cumulative score from successful invasions. Adds a small OP multiplier and affects population bonuses. Gained and lost based on target size.

**Range** — The ratio of the target's land to the attacker's land, expressed as a percentage. Governs prestige outcomes, land generation, and some unit perks.

**Overwhelmed** — A condition where the attacker's OP falls significantly below the defender's DP. Triggers harsh penalties.

**Boats** — Naval transport. Units that require boats cannot be sent beyond available boat capacity. Boat supply is protected by Docks and can be destroyed by certain units.

**Draftees** — Untrained civilians currently converting to military. Contribute 1 DP each on defense. Required to train new units.

---

## Unit Training

### Training Flow

Training converts draftees into combat units via a queue. Resources and draftees are spent immediately; units become available after the training duration completes.

**Training durations:**
- Specialist units (slots 1–2): arrive faster
- Elite units (slots 3–4), spies, assassins, wizards, archmages: arrive slower

### Training Costs

Every unit training order costs:
- **One draftee per unit** — always, for all unit types.
- **Platinum** — primary cost.
- **Secondary resources** (ore, mana, lumber, gems) — varies by unit; defined in race YAML.

**Specialist support units (spies, wizards)** cost only platinum and one draftee.

**Promoted units (assassins, archmages)** are trained by converting a spy or wizard into a more powerful form. The source unit is not consumed — it is promoted.

### Training Cost Reductions

A composite multiplier reduces training costs:
- **Smithy buildings** — the primary cost-reduction building. Scales with Smithy land percentage up to a cap.
- **Technology perks** — additive reductions unlocked through the tech tree.
- **Hero perks** — some heroes reduce training costs.
- **Spell effects** — certain spells temporarily reduce costs.

---

## Exploration

Before land can be built upon, it must be explored. Exploration is a military-adjacent action — it costs platinum, draftees, and morale.

### Exploration Mechanics

- The player selects how many acres of each land type to explore.
- Platinum and draftees are spent immediately.
- Morale drops proportionally to the amount of land explored.
- Land arrives in queue, completing after a delay.

### Exploration Limits

- A single exploration order cannot exceed a percentage of current total land size.
- This limit includes land already in exploration and invasion queues.

### Exploration and Morale

Each acre explored drains morale. Because morale must be at or above a threshold to invade, heavy exploration directly delays an attacker's next invasion window. Players must pace exploration to avoid locking themselves out of combat.

---

## Invasion

### Success Condition

An invasion succeeds when: **Attacker OP > Defender DP**

This is a deterministic comparison — there is no random roll on the outcome itself. If OP exceeds DP by even one point, the invasion succeeds.

### Pre-Invasion Validation

The game enforces several rules before an invasion can be submitted:

**Morale minimum** — Morale must meet a minimum threshold. Attacking below this is blocked entirely.

**5:4 Rule** — The offensive power being sent cannot exceed 1.25× the defensive power remaining at home. This prevents players from sending their entire army while leaving home completely stripped.

**40% Rule** — After accounting for units currently away on other invasions, at least 40% of total DP must remain at home. This ensures a baseline home defense is always present.

**Boat requirement** — Units that need boats to travel overseas can only be sent up to the available boat capacity.

**Range validation** — The target must be within legal invasion range (see Range below).

**Realm restrictions** — Cannot invade realmmates, protected dominions, or cross-round targets.

### Offensive Power Calculation

```
Final OP = Raw OP × OP Multiplier × Morale Multiplier
```

**Raw OP** is the sum of each unit's effective power:
- Base unit offense stat
- All applicable unit perks (land-scaling, building-scaling, prestige-scaling, wizard-ratio, spell bonuses, pairing bonuses, staggered range bonuses, versus-race bonuses, versus-building modifiers)
- Summed across all units sent

**OP Multiplier** is a composite of additive bonuses applied to a 1.0 base:
- Gryphon Nest buildings (scales with Nest land %, capped)
- Race perk offense bonus
- Forges castle improvement (up to ~30%)
- Technology bonuses
- Active spell bonuses
- Wonder bonuses
- War bonus (mutual escalated war > one-sided escalated war > no war)
- Prestige bonus (prestige / 10,000 added to multiplier — large prestige pools provide a small but real edge)
- Hero perks

**Morale Multiplier:** `clamp(0.9 + morale / 1000, 0.9, 1.0)`
- Full morale: no penalty. Zero morale: -10% OP.

### Defensive Power Calculation

```
Final DP = Raw DP × DP Multiplier × Morale Multiplier
```

**Raw DP** is the sum of each defending unit's effective power (same perk resolution as OP), plus all draftees at 1 DP each. Draftees form the defensive floor even with no trained defenders.

**DP Multiplier** draws from:
- Guard Tower buildings (same scale/cap pattern as Gryphon Nests)
- Race perk defense bonus
- Walls castle improvement (up to ~30%)
- Technology bonuses
- Active spell bonuses
- Wonder bonuses

**Temple reduction:** The attacker's Temple buildings reduce the defender's DP multiplier. Each percentage point of the attacker's land occupied by Temples shaves off a portion of the defender's effective multiplier, up to a cap. This makes Temples an offensive tool — attackers with heavy Temple investment effectively weaken every target they hit.

**Minimum defense:** A dominion always has a minimum DP floor based on land size. Small dominions cannot be reduced to zero defense.

### The Overwhelmed Condition

If the attacker's OP falls short of the defender's DP by 20% or more, the attacker is **overwhelmed**. This triggers severe consequences:
- Attacker suffers double casualties.
- Defender takes zero casualties.
- No land changes hands.
- No prestige gained; prestige may be lost.
- Boats are not sunk.

Overwhelmed invasions are not simply failed invasions — they are catastrophic failures. The threshold exists to discourage reckless attacks and punish poor planning.

---

## Land and Range

### Range

Range is the ratio of the target's land to the attacker's land, expressed as a percentage:

```
Range = (Target Land / Attacker Land) × 100%
```

Range determines:
- **Prestige outcomes** — hitting a target below a threshold yields no prestige or a prestige loss; hitting an equal or larger target yields prestige gain.
- **Land generation** — hitting within a favorable range generates bonus land on top of the conquered acres.
- **Unit perks** — some unit abilities (staggered bonuses, immortality conditions, conversion triggers) activate only at specific range thresholds.

### Land Lost by Defender

The amount of land the defender loses scales with the attacker's land size and the land ratio between the two dominions. The formula produces a non-linear result:
- At very low ratios (small attacker vs. large target), land loss is reduced — small dominions cannot strip large ones efficiently.
- At roughly equal ratios, land loss is near its baseline maximum.
- At high ratios (large attacker vs. small target), the absolute number is constrained.

A minimum floor of 10 acres is always lost on a successful invasion regardless of formula output.

War status increases land loss: mutual escalated war amplifies it more than one-sided escalation.

### Land Gained by Attacker

The attacker receives both the **conquered land** (exactly what the defender lost) and **generated land** (a bonus amount based on the conquest). Under normal conditions this is a 1:1 ratio. Land arrives in queue and returns home with the units after a delay.

**Repeat invasion penalty:** If the same target is hit multiple times in a short window, generated land drops to zero. Only the raw conquered land is received on repeat hits.

**Bottom feeding penalty:** Hitting significantly smaller targets reduces land generation and conversion bonuses.

---

## Casualties

### Offensive Casualties

On a **successful invasion:**
- Casualties are calculated on the units needed to break the target's DP, not all units sent.
- Base rate applied to that "units needed" subset.
- Each unit slot's casualties are further modified by its individual casualty perks (reduction, fixed, immortal, etc.).

On a **failed invasion:**
- Casualties apply to all units sent.
- If overwhelmed, the rate is doubled.

### Defensive Casualties

On a **successful invasion:**
- Base casualty rate is modified by:
  - Land ratio (hitting a much smaller target reduces defensive casualties)
  - OP/DP ratio (how decisively the attacker won)
- Recent invasion protection: defenders who have been hit multiple times in a short window suffer progressively fewer casualties on subsequent hits. This prevents a single dominion from being wiped out by coordinated realm-stack attacks.
- Capped between a minimum and maximum percentage.

On a **failed invasion:**
- Scales by how close the attacker came to breaking through. An attacker who barely failed deals near-normal defensive casualties; an overwhelmed attacker deals zero.

### Casualty Modifiers

Individual unit perks override the base casualty system:
- **Percentage reduction** — multiplies the unit's casualties downward.
- **Fixed casualties** — bypasses all other modifiers; the unit always suffers a specific percentage.
- **Immortal** — unit takes zero casualties. Conditional variants exist (immortal when paired, immortal vs. most races, immortal in certain ranges).
- **Rebirth** — the unit "dies" but reappears in queue after a set number of hours.

---

## Prestige

### Prestige Gain and Loss

Prestige changes are determined by the range of the target:

| Range | Outcome |
|---|---|
| Below threshold | Prestige loss (regardless of win or loss) |
| Low range | No prestige change |
| Full range (≥75%) | Prestige gain on success; prestige loss if overwhelmed |
| High range (>119%) | No prestige change (target too large) |

Positive prestige is queued — it returns home with the slowest units rather than being awarded immediately.

### Habitual Invasion Penalty

Repeatedly hitting the same target reduces prestige earned per hit. The penalty multiplier gets progressively worse with each additional hit and has a floor — beyond a certain point, hits yield near-zero prestige. This prevents farming a single weak target for sustained prestige income.

### Prestige Effects

- **OP bonus** — scales with total prestige as a small multiplier addition. Large prestige pools (from sustained successful raiding) provide a compounding combat advantage.
- **Population bonus** — prestige contributes a multiplicative bonus to max population.
- **Food production bonus** — prestige improves food output proportionally.

---

## Boats

### Boat Capacity

Each boat carries a fixed number of units. The capacity per boat can be increased by race perks and technology.

Only units with the boat requirement consume capacity. Some races and some specific units bypass the boat requirement entirely (they can always cross water without boats).

### Boat Generation

Docks generate boats slowly over time. The generation rate increases as the round progresses — Docks built early in the round accumulate more boats by mid-round than Docks built late. Harbor castle improvement multiplies boat output.

### Boat Protection

Docks protect a number of boats from sinking each round. The protection per Dock scales upward as the round ages. Boats beyond the protected threshold are **vulnerable**.

### Boat Destruction

- Units with the `sink_boats_offense` perk sink a portion of the defender's unprotected boats on a successful invasion.
- Units with the `sink_boats_defense` perk sink a portion of the attacker's returning boats on a successful defense.
- Sink amounts are proportional to how many such units are present relative to total force size.
- If the attacker is overwhelmed, no boats are sunk in either direction.

---

## Morale

### Combat Effect

Morale directly scales both OP and DP via the Morale Multiplier:

```
Morale Multiplier = clamp(0.9 + morale / 1000, 0.9, 1.0)
```

At full morale (100), no penalty. At zero, both OP and DP are reduced by 10%. Because the game uses a 0–100 scale but the formula divides by 1000, the practical effect is a linear interpolation across the range.

### Morale Loss

- **Exploration** — each acre explored costs morale. The cost scales with the size of the exploration.
- **Invasions** — each invasion costs morale. Additional morale is lost when attacking targets significantly below the player's size.

### Morale Recovery

Morale recovers passively each tick. Recovery is faster at low morale and slower near the cap. This means a depleted dominion recovers quickly back to the invasion threshold but approaches full morale more gradually.

### Invasion Threshold

Morale must meet a minimum to invade. This prevents players from continuously attacking and forces rest periods between sustained offensive campaigns.

---

## Invasion Resolution Sequence

A successful invasion triggers the following sequence:

1. **Validate** all pre-invasion rules.
2. **Calculate** OP vs. DP and determine success/overwhelmed status.
3. **Resolve boat interactions** — sinking, capacity usage.
4. **Apply prestige changes** — attacker and defender prestige adjusted.
5. **Calculate and apply defensive casualties** — to defender's units.
6. **Calculate and apply offensive casualties** — to attacker's units.
7. **Resolve unit conversions** — any units with conversion perks create new units.
8. **Queue returning units** — attacker's surviving units begin their return journey.
9. **Apply morale changes** — attacker morale decreases.
10. **Distribute land** — defender loses land (barren first, then buildings); attacker gains conquered + generated land in queue.
11. **Award research points** — attacker gains RP on qualifying hits; defender loses RP.
12. **Handle overpopulation** — if the defender's population exceeds capacity after losses, units may desert.
13. **Award hero experience** — attacker hero gains XP; defender hero may lose XP.
14. **Record statistics** — update invasion success/failure counters.
15. **Create game event** — log the invasion in public history.
16. **Send notifications** — notify the defender.

---

## Unit Conversions

Certain races have units that convert enemy population or units into new friendly units as a byproduct of combat. The conversion amount scales with units sent and a unit-specific conversion rate. Conversions are reduced or eliminated when:
- Attacking targets significantly below the attacker's size (bottom feeding).
- The invasion fails.

Converted units arrive in queue alongside returning troops.

---

## Unit Return Times

Units return home after an invasion completes. Return time depends on the unit type, modified by `faster_return` unit perks. Land gained also returns at a similar cadence. Prestige (when positive) returns with the slowest unit group.

During the return window, those units are unavailable for defense at home. This is why the 40% Rule matters — a dominion that sends too many units out simultaneously becomes dangerously exposed.

---

## Interactions With Other Systems

- **[Races & Units](01-races-and-units.md)** — All unit stats, perks, conversion rates, casualty modifiers, and boat requirements are race-specific.
- **[Land & Construction](02-land-and-construction.md)** — Invasions redistribute land. Gryphon Nests and Guard Towers multiply OP/DP. Temples reduce enemy DP. Barracks provide unit housing.
- **[Population & Resources](03-population-and-resources.md)** — Morale affects combat power. Training costs resources. Prestige multiplies food production and max population. Land loss triggers building destruction.
- **[Magic](05-magic.md)** — Spells can temporarily boost OP or DP. Temples interact with the enemy's DP multiplier. Certain spells protect draftees from defensive casualties.
- **[Heroes](07-heroes.md)** — Heroes gain XP from invasions. Hero perks can boost OP multiplier, reduce training costs, or reduce morale loss.
- **[Technology](08-technology.md)** — Tech adds to training cost reductions, OP/DP multipliers, boat capacity, and research point generation from invasions.
- **[Wonders](09-wonders.md)** — Wonder bonuses apply realm-wide OP/DP multipliers and war-escalation land bonuses.

---

## Player Decision Space

**Army composition** — The most consequential ongoing choice. Pure offensive armies maximize OP but leave home vulnerable; mixed armies are harder to break but hit softer. Pairing bonus perks reward keeping specific unit ratios.

**Spec vs. Elite efficiency** — Specialist units (slots 1–2) have lower OP/DP values per unit, making them population-inefficient — more bodies are needed to reach the same total power. However, they are cheap and train quickly, making them ideal for rapid early scaling. Elite units (slots 3–4) have higher OP/DP values per unit, meaning fewer units are needed for the same power output. This frees up population for peasants, improving platinum income. Elites cost more resources and train slower, so they represent a long-term investment: expensive to build up but economically superior once established, since each elite ties up less population than the equivalent power in specs.

**Attacker vs. Explorer economy** — Attackers must maintain both offensive and defensive units, which means a larger share of their population is consumed by military. Explorers only need defense — their offensive "action" (exploring) costs platinum and draftees, not trained offensive units. Because attackers tie up population in offense that contributes nothing to defense (and vice versa), they have fewer peasants producing platinum than an equivalently-sized explorer. This is the fundamental economic cost of aggression: attackers trade platinum income for the ability to take land by force, while explorers enjoy a leaner military and stronger economy at the cost of slower, more predictable growth.

**When to invade** — Timing relative to morale state, target morale, war status, and the 40% home defense rule. Hitting too frequently drains morale. Hitting too rarely loses momentum and prestige income.

**Target selection** — Range determines prestige outcome. Hitting targets at full range (75%+) earns prestige; bottom-feeding costs it. Larger targets yield more land per hit but are harder to break.

**Exploration pacing** — Each exploration drains morale, delaying the next invasion. Players who over-explore cannot maintain consistent raid tempo.

**Boat investment** — Critical for races that require boats. Insufficient boats cap the number of units that can be sent. Harbor investment and Dock building become high priority for naval-dependent races.

**Temple vs. Guard Tower vs. Gryphon Nest** — All compete for Hill and Mountain land. Temples are uniquely offensive (reducing enemy DP) while also helping population growth. The correct balance depends on whether the dominion is primarily attacking, defending, or both.

> **Note:** The 40% Rule and 5:4 Rule work together to prevent complete offensive commitment. No matter how confident an attacker is, a meaningful home guard is always enforced by the game engine.
