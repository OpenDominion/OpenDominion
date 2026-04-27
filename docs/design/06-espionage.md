# Espionage

## Overview

Espionage is the third competitive action system alongside military and magic. Spies and assassins gather intelligence, steal resources, disrupt enemy operations, and perform targeted sabotage. Like magic, espionage operates through a success probability governed by the relative strength of attacker versus defender. Unlike military invasions, individual spy operations are lower-stakes — they affect margins rather than territories — but sustained espionage pressure can meaningfully degrade an enemy dominion or swing resource contests within a realm.

---

## Core Concepts

**Spy Ratio** — The ratio of a dominion's espionage strength (spies + assassins + partial spy units) to its total land. The primary driver of operation success rates.

**Spy Strength** — A stamina resource (0–100+) consumed by every operation. Falls below a threshold to lock out all espionage. Recovers passively each tick.

**Spy Mastery** — An accumulating score earned through successful war and black operations. Provides passive bonuses to spy strength recovery and spy loss reduction.

**Operation Category** — Determines timing restrictions, strength cost, success formula, and consequences on failure: information gathering, resource theft, black operations, or war operations.

---

## Spy Ratio

### Calculation

```
Raw Spy Ratio = (Spies + Assassins×2 + Partial Spy Units) / Total Land
```

Assassins count as double the spy value of a regular spy. Some unit types carry a `counts_as_spy` perk that contributes a fractional spy equivalent (e.g., Halfling Master Thief, Lizardfolk Chameleon). This allows those races to maintain meaningful spy ratios through their military composition.

### Multiplier

The raw ratio is scaled by a composite multiplier drawn from:
- **Race perks** — some races have inherent spy power bonuses, potentially split between offensive and defensive spy power.
- **Spells** — active self-spells can boost spy power.
- **Technology** — tech unlocks add to the multiplier.
- **Wonders** — realm-level bonuses can apply.
- **Hero perks** — certain heroes amplify spy effectiveness.

Offense and defense multipliers are resolved separately, matching the same pattern as wizard ratios in the magic system.

---

## Spy Strength

Spy strength is the operational stamina pool. Every operation costs spy strength. Below the minimum casting threshold, no operations can be performed.

### Recovery

Spy strength regenerates passively each tick at a base rate, modified by:
- **Race perk** — some races have faster spy strength recovery.
- **Technology** — tech perks can add to recovery rate.
- **Spy mastery** — up to 2 additional points per tick at maximum mastery.

### Strength Cost by Category

| Category | Strength Cost |
|---|---|
| Information gathering | Lower cost (2 points) |
| Resource theft | Higher cost (5 points) |
| Black operations | Higher cost (5 points) |
| War operations | Higher cost (5 points) |

Information gathering is the cheapest category by design — scouting should be accessible more frequently than aggressive operations. Hero perks and realm roles (Black Guard membership) can further reduce information gathering costs.

---

## Operations

### Information Gathering

Scouting operations that reveal a snapshot of the target's current state. Always creates an Info Op record visible in the Op Center. Does not award hero XP on repeated casts within a freshness window (preventing XP farming through repeated scouting).

| Operation | Reveals |
|---|---|
| Barracks Spy | Military unit counts (estimates) |
| Castle Spy | Castle improvement levels |
| Survey Dominion | Building quantities |
| Land Spy | Land type distribution |

**Timing:** Available throughout the round with no restriction window.

**Success formula:** Uses the easier curve (0.8 base exponent) compared to hostile operations.

### Resource Theft

Operations that transfer a portion of the target's stockpile to the attacker. Six resources can be stolen: platinum, food, lumber, mana, ore, and gems.

**Restrictions:**
- Cannot perform within the first 3 days of the round.
- The target must be at least as large as the attacker (cannot steal from smaller dominions).
- Cannot steal from NPC (bot) dominions.

**Theft Amount**

Each theft operation calculates a maximum stolen amount as the **minimum** of three independent ceilings:

1. **Target ceiling** — a percentage of the target's current stockpile of that resource.
2. **Attacker ceiling** — a percentage of the attacker's own hourly production of that resource. This cap prevents a rich target from being drained beyond what the attacker could practically carry.
3. **Carry capacity** — scales with the attacker's spy ratio and total land. Higher spy ratio means more can be carried per operation.

The final amount is then multiplied by any applicable tech or spell theft gains.

**Protection against theft:**
- **Fool's Gold** spell protects against platinum theft entirely. With the upgraded tech variant, it also protects ore, lumber, and mana.
- Tech perks on the target can increase the proportion of their resources that is "lost" on theft (paradoxically making theft more damaging to them, not a defense).

**Success formula:** Uses the harder curve (0.7 base), same as black operations.

### Black Operations

Targeted sabotage that damages enemy military or disrupts readiness. Available after the first 3 days of the round but requires no war declaration.

**Assassinate Draftees** — Kills a percentage of the target's draftee pool. Draftees are the source of all new unit training, so consistent draftee assassination degrades an enemy's military build-up capacity.

### War Operations

High-impact sabotage reserved for wartime or post-invasion windows. Require one of:
- A formal war declared between the attacker's realm and the target's realm.
- The attacker (or a realmmate) having invaded the target within the last 12 hours.
- Mutual Black Guard membership between both realms.

Available after the first 3 days of the round.

| Operation | Primary Effect |
|---|---|
| Assassinate Wizards | Kills a percentage of the target's wizards |
| Magic Snare | Reduces the target's wizard strength (not count) |
| Sabotage Boats | Destroys a percentage of the target's unprotected boats |
| Incite Chaos | Increases the target's chaos score |
| Assassinate Archmages *(Spirit only)* | Kills a percentage of the target's archmages |

**Assassinate Wizards** is a direct counter to heavy magic investment. Forest Haven buildings (currently inactive in live builds) were intended as a defensive building specifically for wizard protection.

**Magic Snare** does not kill wizards — it drains their strength, pushing them toward or past the snare threshold. The target gains resilience as a side effect, accelerating their recovery. The design intent is temporary suppression rather than permanent attrition.

**Sabotage Boats** destroys only unprotected boats. Boats protected by Dock buildings and Harbor improvements are immune. This creates a defensive investment decision: Docks protect boats from both military unit sinking (during invasions) and sabotage.

**Incite Chaos** raises the target's chaos score. At high chaos, the target risks critical failures in their own offensive spells that reflect back onto the caster. The saboteur also loses some of their own chaos in the process — the ability transfers chaos rather than simply creating it.

---

## Success Probability

### Formula

Success depends on the **relative spy ratio** (attacker's ratio divided by target's ratio):

- **Information gathering** — easier curve. Approaching equal ratios yields roughly 50% success; outmatching the defender yields rapidly increasing odds.
- **Theft and hostile operations** — harder curve. Requires a more meaningful ratio advantage to reach the same success probabilities.

A secondary modifier adjusts success based on the **spy strength differential** between caster and target. The effect is small relative to the ratio comparison but can tip marginal situations.

**Modifiers reducing success:**
- Target's **Illumination** friendly spell (provides a chance for hostile ops to fail).
- Target's espionage-defense wonder bonuses (applied multiplicatively).

**Caps:** Success is clamped between a minimum floor (~1%) and a maximum ceiling (~97–98%).

---

## Failure and Spy Losses

When an operation fails, the attacker loses spies (and potentially assassins and spy-equivalent units) to capture or death.

### Loss Rate

The base loss percentage scales inversely with the ratio advantage: attacking a target with a much higher spy ratio than the attacker results in severe losses; attacking a ratio-matched or weaker target yields smaller losses.

The rate is clamped to a minimum and maximum for each operation category:
- Information gathering: smallest loss range (these are lower-risk operations by design).
- Theft and hostile: larger loss range.

**Loss multipliers:**
- **Spy mastery** — reduces losses, up to 50% reduction at maximum mastery.
- **Spell perks** — attacker's active spells can reduce spy losses.
- **Technology** — attacker tech can reduce spy losses.
- **Hero perks** (target) — the defender's hero can increase spy losses inflicted.
- **Mutual war** — 20% reduction to spy losses on failure during active war.
- **Hard floor** — loss reduction cannot exceed 80% total (minimum 20% of base losses always apply).

Losses are also capped proportional to total land, preventing a single failed operation from wiping out an entire spy force.

### Black Guard Recovery

Members of the Black Guard (a realm-level organization) recover a portion of spies lost on failed operations — they are re-queued for training rather than permanently lost. Non-Black Guard assassins partially convert to spies when recovered.

### Demon Charm

The Demon race's Succubus unit has the `charm_spies` perk. When spies fail against a Demon dominion, a portion of the captured spies are charmed and join the Demon's military rather than being executed. The charm rate scales with the number of Succubus units present.

---

## Notifications and Identity

### What the Target Sees

- **Failed operation:** The target is always notified that a spy operation was repelled, including the type of operation and how many units were killed.
- **Successful hostile operation:** The target is notified with the operation type and the damage dealt.
- **Successful info op or theft:** By default, the target does not know they were successfully scouted or robbed.

### Surreal Perception

If the target has the Surreal Perception spell active, they receive notification of all successful spy operations against them, including the source. This spell converts the espionage system from covert to overt for the defender — they know who is targeting them even when operations succeed.

### Identity Hiding

Hero perks can hide the attacker's identity when an operation fails. If the hero perk is active and the target does not have Surreal Perception, failed operations show no source dominion in the notification. This enables covert pressure campaigns without diplomatic repercussions.

---

## Spy Mastery

Mastery accumulates through successful war and black operations (not information gathering or theft). The gain per operation is based on the difference in mastery scores between attacker and target: hitting a higher-mastery target awards more, matching or beating a lower-mastery target awards standard amounts.

A corresponding mastery loss applies to the attacker if they are already at significant mastery — high-mastery players gradually lose rating against lower-mastery opponents, creating a drift toward equilibrium.

**Mastery benefits:**
- **Spy strength recovery** — increases per-tick recovery up to a cap at maximum mastery.
- **Spy loss reduction** — reduces losses on failure, up to 50% at maximum mastery.

---

## Interactions With Other Systems

- **[Races & Units](01-races-and-units.md)** — Race spy power perks, faster spy strength recovery perks, and `counts_as_spy` units all feed directly into espionage capability. Demon Succubus charm perk and Halfling spy-defense bonuses are unit-level espionage interactions.
- **[Land & Construction](02-land-and-construction.md)** — Spy ratio is land-normalized; growing land without growing the spy force dilutes ratio. Docks protect boats from Sabotage Boats.
- **[Population & Resources](03-population-and-resources.md)** — Theft operations drain resources. Assassinate Draftees impacts the training pipeline. Platinum is the most commonly contested theft target.
- **[Military](04-military.md)** — Spies and assassins occupy population and must be trained from draftees. Magic Snare weakens the target's magical defense, potentially enabling easier hostile spells. Sabotage Boats cripples naval invasion capacity.
- **[Magic](05-magic.md)** — Disband Spies (hostile spell) converts enemy spies to draftees — a magical counter to espionage. Illumination friendly spell protects realmmates from spy ops. Surreal Perception reveals spy op sources.
- **[Heroes](07-heroes.md)** — Hero perks modify spy losses, info op costs, identity hiding, theft gains, and operation damage.
- **[Technology](08-technology.md)** — Tech perks add to spy power, spy loss reduction, theft gains, and op cost reductions.
- **[Wonders](09-wonders.md)** — Wonders can provide realm-wide spy power bonuses and reduce enemy operation success rates.

---

## Player Decision Space

**Spy ratio vs. army size vs. wizard ratio** — All three compete for the same population pool (all require draftees to train). A dominion cannot fully maximize military, magic, and espionage simultaneously. Spy-focused races invest heavily here; others maintain a baseline and rely on realmmates for espionage support.

**Theft targeting** — The size restriction (cannot steal from smaller targets) shapes the theft economy: only dominant players can be robbed, and only by someone close to or smaller than their size. Theft is a tool for the second-tier players in a competitive bracket, not for farming weak targets.

**Operation timing around war** — War operations require a war or recent invasion. Players who want war op access must either coordinate realm-level war declarations or pair espionage with their own military attacks.

**Spy strength pacing** — Burning through spy strength on cheap info ops leaves nothing for high-cost war operations. Players engaged in active conflict must prioritize high-value ops and let strength recover between cycles.

**Surreal Perception response** — Knowing an enemy has Surreal Perception changes the calculus: every successful operation will reveal the attacker's identity, creating diplomatic consequences. This may favor sending realmmates who are less exposed, or timing operations during declared war when identity matters less.

**Black Guard membership** — The spy loss recovery mechanic makes Black Guard membership especially valuable for espionage-heavy dominions. Losing 75% fewer permanent spies on failure dramatically lowers the cost of aggressive spy campaigns.

> **Note:** Espionage creates an information asymmetry that compounds over time. A dominion with high spy ratio and active scouting has a complete picture of their enemies' builds, military composition, and spell loadouts. This informational edge translates directly into better military targeting and counter-spell decisions — the value of espionage extends well beyond the direct damage of any single operation.
