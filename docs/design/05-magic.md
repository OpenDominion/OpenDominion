# Magic

## Overview

Magic is one of three competitive action systems in OpenDominion (alongside military and espionage). Wizards and archmages generate spell-casting capacity. Self-spells provide temporary economic and military buffs. Hostile and war spells damage enemy dominions. Info spells reveal enemy information. Friendly spells support realmmates. All offensive spell casting is governed by a success probability that scales with the caster's wizard ratio relative to the target's, creating a parallel magical arms race alongside the military one.

---

## Core Concepts

**Wizard Ratio** — The ratio of a dominion's magical strength (wizards + archmages + partial wizard units) to its total land. The primary driver of offensive spell success rates and some unit perks.

**Wizard Strength** — A resource (0–100+) consumed by casting spells. Recovers passively each tick. Dropping below a threshold locks out all spell casting until recovery occurs.

**Mana** — The resource consumed per spell cast. Scales with land size (cost = mana multiplier × total land). Decays each tick.

**Spell Duration** — How many hours a buff or debuff remains active. Most spells can be extended by recasting before expiry.

**Wizard Mastery** — An accumulating score earned through successful offensive casts. Provides small bonuses to mana cost and wizard strength recovery over time.

**Chaos** — A score earned by Chaos League members through critical successes in offensive spells. High chaos enables critical failures that reflect spells back onto the caster.

**Resilience** — A score that buffs wizard strength recovery when a dominion is "snared" (wizard strength below the casting threshold). Decays naturally each hour.

---

## Wizard Strength

Wizard strength is the casting stamina pool. Every spell cast costs a fixed amount of wizard strength. If strength falls below the minimum threshold, no spells can be cast until it recovers.

### Strength Recovery

Wizard strength regenerates each tick at a base rate, modified by:
- **Race perk** — some races have higher wizard strength recovery.
- **Technology** — tech perks can add to the recovery rate.
- **Wizard mastery** — up to a bonus of 2 points per tick at maximum mastery.
- **Resilience bonus** — when snared (below casting threshold), resilience adds an additional recovery bonus equal to the current resilience value divided by a constant. This creates an accelerated recovery period for dominions that have been depleted.

### Being Snared

A dominion whose wizard strength falls below the casting minimum cannot cast any spells — not even self-buffs. This is the magical equivalent of a military dominion at zero morale: a temporary lockout that self-resolves given time. The resilience mechanic ensures this lockout doesn't last disproportionately long.

---

## Wizard Ratio

The wizard ratio is the fundamental measure of a dominion's magical capability.

### Calculation

```
Raw Wizard Ratio = (Wizards + Archmages×2 + Partial Wizard Units) / Total Land
```

Some unit types (via the `counts_as_wizard` perk) contribute a fractional wizard count. This allows races with wizard-like military units to maintain meaningful wizard ratios without training dedicated wizard personnel.

### Multiplier

The raw ratio is multiplied by a composite modifier based on:
- **Race perks** — some races have inherent wizard power bonuses, potentially split between offensive and defensive wizard power.
- **Spell perks** — active spells can boost wizard power.
- **Technology** — tech unlocks add to the multiplier.
- **Wonders** — realm-level bonuses can boost wizard power.
- **Hero perks** — certain hero abilities amplify wizard effectiveness.
- **Spires castle improvement** — boosts offensive wizard ratio specifically.

The offense and defense multipliers are resolved separately, so a dominion can be stronger at casting hostile spells than at resisting them (or vice versa).

---

## Spell Categories

### Self Spells

Buffs cast on the caster's own dominion. Self spells always succeed — there is no success roll. They apply immediately and last for a fixed duration.

Generic self spells are available to all races. Racial self spells are restricted to one or a few specific races and are typically more powerful than generic equivalents.

**Key design note:** Self spell bonuses of the same type do not stack — only the highest value applies. A dominion cannot double-stack two different +10% offense spells.

**Amplify Magic** is a special self spell that, when active, doubles the mana cost and increases the duration of the next non-cooldown self spell cast. The Amplify Magic spell is consumed immediately after the buffed spell resolves. This creates a mana-intensive but duration-efficient casting pattern for dominions willing to spend heavily on magic.

**Cooldown spells** — some self spells have a cooldown period after casting, preventing immediate recasting.

### Hostile Spells (Black Ops)

Debuffs cast against enemy dominions. Subject to a success roll based on relative wizard ratios. If successful, the effect applies to the target for a fixed duration. If it fails, the caster loses wizards and archmages at a rate that scales with how outmatched the caster was.

**Timing restrictions:** Hostile spells can only be cast at least 3 days into the round. Chaos League members have fewer restrictions.

**War bonuses:** Hostile spell durations are extended when the caster's realm is at war. Mutual (escalated) war provides a larger duration bonus than one-sided war.

### War Spells

Instant-damage spells that destroy enemy resources, buildings, or population. Require that a war is active, or that the caster has recently invaded the target, or that the caster is a Chaos League member. Like hostile spells, they are subject to a success roll.

War spells can trigger **status effects** (Burning, Lightning Storm) that further modify combat dynamics between the two parties.

### Info Spells

Reveal information about the target: their status, technologies, active spells, or heroes. Slightly easier to succeed than hostile spells. Information gathered is stored as a snapshot (Info Op) for later reference.

### Friendly Spells

Beneficial spells cast on realmmates. Restricted to designated realm roles (Grand Magister, Court Mage) or Chaos League members. Always succeed — no roll required. Subject to a cooldown between recasts.

---

## Spell Listing

### Generic Self Spells

| Spell | Duration | Primary Effect |
|---|---|---|
| Gaia's Watch | 12h | Food production bonus |
| Ares' Call | 12h | Defense bonus |
| Midas Touch | 12h | Platinum production bonus |
| Mining Strength | 12h | Ore production bonus |
| Harmony | 12h | Population growth bonus (large) |
| Fool's Gold | 10h | Protects against platinum theft; cooldown applies |
| Surreal Perception | 12h | Reveals who is casting spells and operations on you |
| Energy Mirror | 12h | Reduces incoming hostile spell damage and duration |
| Amplify Magic | 6h | Doubles mana cost and boosts duration of next self spell; consumed on use |

### Racial Self Spells (Selected)

| Spell | Races | Primary Effect |
|---|---|---|
| Crusade | Human, Nomad | Offense bonus |
| Favorable Terrain | Nomad | Offense bonus from barren land |
| Miner's Sight | Dwarf, Gnome | Ore production bonus; protects ore from Earthquake |
| Killing Rage | Goblin | Offense bonus |
| Alchemist Flame | Firewalker | Increases Alchemy platinum and forge output |
| Erosion | Lizardfolk, Merfolk | Auto-rezones conquered land to water |
| Alchemist Frost | Icekin | Platinum production bonus |
| Bloodrage | Orc | Offense bonus (with increased offensive casualties) |
| Unholy Ghost | Dark Elf, Spirit | Enemy draftees do not contribute DP in invasions against you |
| Frenzy | Halfling | Spy power bonus, reduced spy losses |
| Finders Keepers | Halfling | Theft gains and success bonus |
| Howling | Kobold | Offense and defense bonus |
| Verdant Bloom | Sylvan | Auto-rezones conquered land to forest |
| Regeneration | Troll | Casualty reduction |
| Feral Hunger | Lycanthrope | Werewolves convert enemy peasants into werewolves |
| Death and Decay | Undead | Accelerates food/lumber decay; converts peasants to zombies; cooldown applies |
| Feast of Blood | Vampire | Vampire conversion bonus |
| Infernal Command | Demon | Offense bonus for Infernal Imps; applies Corruption to target |
| Gaia's Light | Wood Elf | Wizard power bonus, spy power penalty |
| Gaia's Shadow | Wood Elf | Spy power bonus, wizard power penalty |
| Delve into Shadow | Chaos League | Exploration cost reduction; refund on failed chaos spells |
| Spellwright's Calling | Dark Elf | Generates Adepts from Wizard Guilds; bonus guild mana output |

### Hostile Spells

| Spell | Duration | Effect |
|---|---|---|
| Plague | 8h | Population growth reduction |
| Insect Swarm | 8h | Food production reduction |
| Great Flood | 8h | Boat production reduction |
| Earthquake | 8h | Gem and ore production reduction |
| Disband Spies | Instant | Converts a percentage of target's spies to draftees |

### War Spells

| Spell | Effect |
|---|---|
| Fireball | Destroys unprotected peasants and food stockpile; chance to apply Burning |
| Lightning Bolt | Destroys castle improvement investment (science, keep, forges, walls); chance to apply Lightning Storm |
| Cyclone | Deals damage to wonders |

### Info Spells

| Spell | Reveals |
|---|---|
| Clear Sight | Target's full status screen |
| Vision | Target's technology research |
| Revelation | Target's active spells |
| Disclosure | Target's heroes |

### Friendly Spells

| Spell | Duration | Effect |
|---|---|---|
| Arcane Ward | 6h | Increases chance of hostile spells failing against target |
| Illumination | 6h | Increases chance of hostile spy operations failing against target |
| Spell Reflect | 3h | Reflects the next incoming hostile or war spell back to caster |

---

## Mana Cost

Spell mana costs scale with land size: `mana cost = base multiplier × total land`. Larger dominions pay more per spell in absolute terms, but the relative burden (mana production is also land-proportional via Towers) stays roughly constant.

The base multiplier is modified by:
- **Technology perks** — can reduce spell cost, self spell cost, or racial spell cost categories separately.
- **Wonder perks** — can reduce spell costs realm-wide.
- **Wizard mastery** — provides a small mana cost reduction that grows with accumulated mastery up to a cap.
- **Amplify Magic** — doubles self spell cost for the next buffed cast.

---

## Offensive Spell Success

All hostile, war, and info spells require a success roll before their effects apply. The success probability is derived from the relative wizard ratios of caster and target.

### Success Formula

The base success chance is an exponential function of the **relative ratio** (caster's wizard ratio divided by target's wizard ratio). The curve has the following character:
- When the caster greatly outmatches the target, success probability approaches the maximum cap.
- At equal ratios, success is meaningful but not guaranteed.
- When the target outmatches the caster, success probability falls steeply.

A secondary modifier adjusts success slightly based on the **difference in wizard strength** between caster and target.

Hostile and war spells have a slightly harder success curve than info spells — they are more sensitive to wizard ratio differences.

**Modifiers reducing success:**
- Target's **Arcane Ward** friendly spell
- Target's spell-defense wonder bonuses

**Caps:** Success probability is clamped between a minimum floor (~1%) and a maximum ceiling (~97–98%). No cast can be guaranteed to succeed or guaranteed to fail.

### Failure Consequences

When an offensive spell fails, the caster loses a portion of their wizards and archmages. The loss rate scales with the target's wizard ratio relative to the caster's — attacking a much stronger target is punished proportionally. Archmage losses are a fraction of wizard losses. Mutual war reduces wizard losses on failure.

---

## War Spell Damage

War spells deal instant, quantified damage rather than applying a timed debuff.

### Fireball

Destroys unprotected peasants and a portion of the food stockpile. Peasant protection is provided by:
- **Wizard Guild buildings** — each guild protects a fixed number of peasants. The protection scales with guild count up to a cap.
- **Spell and tech protections** — additional vulnerability reduction can be unlocked.

Only peasants beyond the protected threshold are at risk. The damage formula applies to the unprotected surplus, capped by total vulnerable population.

### Lightning Bolt

Destroys castle improvement points, specifically from the science, keep, forges, and walls improvements (spires and harbor are exempt). Protection is provided by:
- **Masonry buildings** — each percentage of land occupied by Masonry reduces Lightning Bolt vulnerability by a flat amount, up to 50%.
- **Spires improvement** — provides additional protection.

Like Fireball, only improvement investment beyond the protected threshold is at risk.

### Damage Modifiers

All war spell damage is subject to a composite multiplier that can increase or reduce it:
- **Spires castle improvement** (target) — reduces incoming damage.
- **Wizard Guild buildings** (target, Fireball only) — reduces peasant vulnerability.
- **Masonry buildings** (target, Lightning Bolt only) — reduces improvement vulnerability.
- **Energy Mirror spell** (target) — reduces incoming spell damage and duration.
- **Status effects** (Burning/Lightning Storm) — can amplify subsequent damage of the same type.
- **Hero perks** — caster heroes can increase damage output; target heroes can reduce it.
- **Hard cap:** Damage cannot be reduced below 20% of base (maximum 80% reduction).

**Critical success:** A successful cast has a chance to deal 1.5× damage. This cannot occur alongside a critical failure.

### Status Effects

War spells have a chance to apply persistent status effects:

**Burning** — Applied by Fireball. Increases subsequent Fireball damage to the target for the duration. At expiry, applies Rejuvenation.

**Lightning Storm** — Applied by Lightning Bolt. Increases subsequent Lightning Bolt damage. At expiry, applies Rejuvenation.

**Rejuvenation** — Applied when Burning or Lightning Storm expires. Drastically reduces incoming spell damage and boosts population growth for an extended period. Rejuvenation is cancelled if a new war is declared. Rejuvenation effectively ends a magical assault cycle: the attacker cannot continue with effective war spells while Rejuvenation is active.

These effects create a natural rhythm in magical warfare: an opening assault phase (Burning/Lightning Storm active), a ceiling where damage is amplified, and then a forced cooldown window (Rejuvenation).

---

## Chaos System

Chaos League members interact with an additional mechanic:

**Chaos score** accumulates through critical successes when casting offensive spells. As the chaos score rises, a critical failure chance emerges. A critical failure causes the spell to **reflect back onto the caster** at amplified damage, rather than affecting the target. Critical failures consume chaos and reduce the score.

This creates an inherent risk/reward tension for Chaos League members: accumulating chaos through aggressive casting increases both offensive output (via perks) and self-inflicted risk.

---

## Spell Duration and Stacking

Most spells last a fixed number of hours after casting. Recasting a spell before it expires adds the newly calculated duration to the remaining duration, not replacing it — though the added duration cannot exceed a full fresh cast duration. This incentivizes consistent recasting to maintain buffs.

**War bonuses to hostile spell duration:** When the caster's realm is at war, hostile spell durations extend. Mutual escalated war provides a larger extension than one-sided war.

**Non-stacking rule:** Multiple self spells providing the same bonus type (e.g., two different offense buffs) do not add together. Only the highest value applies. Casting a second, higher-value offense spell replaces the weaker one's contribution.

---

## Friendly Spells and Realm Cooperation

Realm members with designated roles (Grand Magister, Court Mage) can cast buffing spells on their realmmates. Chaos League members also have this ability. Friendly spells always succeed and require no success roll.

Key friendly spells:
- **Arcane Ward** and **Illumination** protect realmmates against incoming spells and ops respectively.
- **Spell Reflect** creates a one-time reflector on a realmmate, bouncing the next hostile or war spell back to its caster.

These spells create a cooperative defensive layer where dedicated magical-support roles can shield vulnerable realmmates during peak threat periods.

---

## Interactions With Other Systems

- **[Races & Units](01-races-and-units.md)** — Race perks control wizard power, wizard strength recovery, and immortal wizard status. Some units count as partial wizards. Racial spells are the strongest self-buffs available and define each magic-capable race's identity.
- **[Land & Construction](02-land-and-construction.md)** — Towers and Wizard Guilds produce mana and provide wizard protection. Spires castle improvement boosts offensive wizard ratio and reduces incoming spell damage. Masonry reduces Lightning Bolt damage. Temples reduce enemy DP (military interaction, not magic directly).
- **[Population & Resources](03-population-and-resources.md)** — Mana production and 2% decay set the sustainable spell cadence. Fireball targets the peasant population. Insect Swarm targets food production.
- **[Military](04-military.md)** — Self spell offense/defense buffs stack onto military multipliers. Unholy Ghost prevents enemy draftees from defending. War status affects hostile spell duration and war spell access.
- **[Espionage](06-espionage.md)** — Illumination friendly spell protects against spy ops. Disband Spies hostile spell converts enemy spies to draftees.
- **[Heroes](07-heroes.md)** — Hero perks can modify wizard power, spell damage output, spell damage resistance, self spell strength cost, and morale loss from casting.
- **[Technology](08-technology.md)** — Tech perks reduce spell costs, extend/resist spell durations, boost wizard power, and improve wizard strength recovery.
- **[Wonders](09-wonders.md)** — Wonders provide realm-wide wizard power bonuses and can reduce enemy spell success rates.

---

## Player Decision Space

**Tower vs. Wizard Guild vs. Temple** — All three compete for Swamp land. Towers maximize mana volume; Wizard Guilds provide peasant protection from Fireball and secondary mana; Temples reduce enemy DP. Choosing the swamp split determines the character of the dominion's magical posture.

**Spires investment** — Spires boost offensive wizard ratio and reduce incoming spell damage. Heavy Spires investment enables more reliable offensive casting against well-defended targets.

**Wizard ratio vs. army size** — Wizards and archmages occupy population but contribute nothing directly to military OP/DP. Investing heavily in wizards improves spell success rates but reduces the military power that can be fielded from the same population.

**Casting frequency vs. stockpiling** — Mana decays at 2% per tick. Sitting on a large mana reserve wastes production. Active casters should cast frequently to consume mana before it decays. Passive dominions should build fewer Towers.

**Racial spell timing** — Racial self spells (5× mana cost) are expensive. Casting them only when they will matter (before an invasion, during an active war) is more efficient than maintaining them continuously.

**Amplify Magic usage** — The Amplify Magic combo (double cost, +50% duration) is efficient over longer time horizons but requires a larger upfront mana payment. Valuable for dominions with ample mana reserves who want to reduce recast frequency.

> **Note:** Wizard strength management is often underappreciated by newer players. Over-casting depletes strength quickly, locking out all spells including self-buffs. Planning cast schedules to stay above the snare threshold — especially before expected invasions — is a meaningful skill expression.
