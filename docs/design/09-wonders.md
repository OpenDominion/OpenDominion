# Wonders

## Overview

Wonders are powerful realm-level objectives scattered across the game world. Each wonder provides a significant passive bonus to every dominion in the controlling realm. Capturing a wonder requires sustained coordinated attack from an entire realm — both military units and Cyclone spells chip away at a wonder's HP until it falls. Control then transfers to the attacking realm (subject to constraints), and the bonus it provides shifts with it. Wonders are a primary source of inter-realm conflict and the central cooperative goal for most realms throughout a round.

---

## Core Concepts

**Wonder Power** — The current HP of a wonder. Starts at a value determined by the round day and tier. Reduced by attacks and Cyclone casts. Reaches zero when destroyed.

**Controlling Realm** — The realm that owns a wonder receives its bonus passively across all member dominions. Neutral wonders provide no bonus to anyone.

**One Wonder Per Realm** — A realm cannot own two wonders simultaneously. Destroying a wonder while already owning one returns it to neutral rather than capturing it.

**Damage Contribution** — Each dominion's share of total damage dealt to a wonder is tracked. This determines prestige and mastery rewards on capture, and influences the rebuild power of the captured wonder.

**Cyclone** — The only magic attack that can damage wonders. A war spell available to all races.

---

## Wonder Tiers

Wonders come in two power tiers:

**Tier 1** — Higher base power. Harder to destroy, with stronger bonus perks (or wider-ranging effects).

**Tier 2** — Lower base power. Easier to destroy, with more focused or specialized perks.

The power of a wonder when it first spawns (or respawns after destruction) scales with the round day, so wonders become progressively more durable as the round advances.

---

## Wonder Catalog

### Tier 1 Wonders

| Wonder | Primary Bonus |
|---|---|
| Obelisk of Power | Offense and defense bonus |
| Great Wall | Defense bonus |
| Gnomish Mining Machine | Ore production bonus |
| Fountain of Youth | Maximum population bonus |
| City of Gold | Platinum production bonus |
| Ruby Monolith | Reduces both offensive and defensive casualties |
| Factory of Legends | Reduces construction platinum cost |
| Golden Throne | Prestige gains bonus from invasions |
| School of War | Additional barracks housing |
| Horn of Plenty | Small bonus to all six production resources |
| Wayfarers Outpost | Platinum production + reduced exploration cost |
| Ancient Library | Castle investment efficiency bonus |
| Planar Gates | Grants use of one tech the realm has unlocked as if permanently owned *(active)* |

### Tier 2 Wonders

| Wonder | Primary Bonus |
|---|---|
| Great Market | Employment bonus + bank exchange rate bonus |
| Guild of Shadows | Spy power bonus + reduced spy losses on failure |
| Hanging Gardens | Food production bonus |
| Halls of Knowledge | Research point production bonus (flat + percentage) |
| Great Oracle | Spell mana cost reduction + wizard power bonus |
| Ivory Tower | Chance to cause hostile spells to fail |
| Underground Society | Chance to cause hostile spy operations to fail |
| Imperial Armada | Boats cannot be sabotaged + guard tax reduction |
| Wizard Academy | Reduces hostile spell damage received |
| Astral Panopticon | Grants the effect of Surreal Perception (reveals all spy/spell ops) to the controlling realm |

### Special Wonder

**Urg, the Devourer** — An extremely high-power sentient wonder with no controlling realm. Every 24 hours, Urg automatically attacks dominions in the three realms that have dealt the least damage to it recently. Urg cannot be captured — it exists as a threat and a mastery-farming target. Attacking Urg earns mastery through Cyclone casts.

---

## Wonder Bonuses

All perks from a controlled wonder apply passively to every dominion in the realm — no action required. Bonus types span every game system:

**Military:** Offense/defense multipliers, casualty reduction, barracks housing, prestige gain rate, faster unit return times.

**Economic:** Platinum production, food production, ore/gem/lumber/mana production, construction cost reduction, castle investment efficiency, exploration cost reduction, exchange rate bonus, employment multiplier.

**Espionage:** Spy power, spy loss reduction, hostile operation failure chance (defensive), boat sabotage immunity.

**Magic:** Wizard power, spell cost reduction, hostile spell failure chance (defensive), hostile spell damage reduction.

**Special:** Surreal Perception realm-wide, tech sharing (Planar Gates), guard tax reduction.

---

## Attacking Wonders

Wonders take damage from two sources: direct military attacks and Cyclone spells.

### Military Attacks

A dominion sends a force against a wonder using the same unit selection interface as invasion but targeting a wonder instead. Key differences from a regular invasion:

**Unmodified offense** — The damage dealt equals the raw offensive power of units sent, scaled only by morale and the wonder attack damage multiplier (from tech/hero). Normal OP multipliers from Gryphon Nests, race perks, Forges, spells, and war bonuses do **not** apply. This levels the playing field and prevents one dominant dominion from trivializing a wonder.

**Fixed casualties** — Every unit sent suffers a flat casualty rate, always. This rate is not reduced by unit immortality, casualty reduction perks, or most other modifiers. The tech perk `casualties_wonders` is a specific exception that can reduce it. Immortal units can die during wonder attacks — they are not immune here.

**Morale and home defense rules apply** — The 40% and 5:4 home defense rules apply normally. Morale must be at least 1. Each attack costs morale.

**War requirement for owned wonders** — Attacking a wonder owned by another realm requires being at war with that realm. Neutral wonders can be attacked by anyone (except Royal Guard members, who cannot participate in wonder attacks at all).

### Cyclone Spells

Cyclone is the war spell that deals magical damage to wonders. Damage scales with the caster's wizard ratio and land size, amplified by wonder damage tech perks and the Cyclone hero upgrade (Staff of the Stormcaller, Blade of Sundering). A single cast's damage is capped at a percentage of the wonder's maximum power — no single cast can one-shot a wonder.

**Double damage on neutral wonders** — Cyclone deals twice normal damage against wonders that are currently unowned. This accelerates the early race to capture a neutral wonder.

Cyclone costs mana and wizard strength like any war spell, and is subject to the same war/recent-invasion requirement.

---

## Wonder Destruction and Capture

When a wonder's power reaches zero:

1. The game checks whether the attacking realm already owns a wonder.
   - **If yes:** The wonder returns to neutral. No realm captures it. A new power value is calculated for the neutral respawn.
   - **If no:** The attacking realm captures the wonder. The wonder rebuilds at a power level scaled by the attacking realm's damage contribution.

2. **Rebuild power on capture** scales with how much damage the capturing realm dealt relative to the total damage to the wonder. A realm that dealt most of the damage rebuilds the wonder at higher power; a realm that only dealt the killing blow on a mostly-dead wonder captures a weaker version. Minimum and maximum rebuild power thresholds apply.

3. **Rebuild power on neutral respawn** is calculated fresh from the round day — it does not carry forward any memory of the destroyed wonder's previous power.

4. All damage records for the wonder are cleared on destruction.

---

## Power Visibility

What a dominion can see about a wonder's current HP depends on their relationship to it:

- **Controlling realm or at war with controlling realm:** Exact current power is visible.
- **All others:** Approximate power (rounded to the nearest 5,000).
- **Unowned wonders:** Shown as unknown until discovered.

This information asymmetry gives the controlling realm an intelligence advantage — they know exactly how much health their wonder has remaining, while attackers must estimate.

---

## Rewards for Participation

### Prestige

Prestige is awarded to dominions in the realm that successfully captures a wonder (not to those who attack neutrals or owned wonders without a capture). Each participating dominion receives a base prestige amount, with additional prestige scaling up to a cap based on how much of the realm's total damage they personally contributed. There is a minimum contribution threshold to receive any prestige at all — minor participants get nothing.

### Wizard Mastery

Awarded to dominions who dealt Cyclone damage to the captured wonder. Same contribution thresholds and scaling as prestige. Rewards dedicated magical participation in the wonder assault.

### Valor

Both attacking and defending realms receive valor awards from wonder combat, categorized separately for attacks on owned versus neutral wonders.

---

## Wonder Lifecycle

**Initial spawn** — Wonders appear at round start and on a recurring schedule (every 48 hours starting partway into the round). The spawning power scales with the round day.

**Power growth over time** — Because spawn power scales with the day, wonders that appear or respawn later in the round are harder to destroy. Early-round wonders are the easiest targets; late-round spawns may be effectively impervious to all but the strongest coordinated realm assaults.

**No regeneration while owned** — A wonder's HP does not regenerate passively while controlled. The only way its power increases is through respawn after destruction.

**Ownership duration** — A realm holds a wonder indefinitely until another realm destroys it. Bonuses persist throughout ownership.

---

## Strategic Implications

### For Attackers

The fixed casualty rate and unmodified offense make wonder assaults inherently costly. A realm that throws a single dominion at a wonder will take heavy losses for minimal progress. Effective wonder assaults require:
- Multiple dominions attacking in sequence to accumulate damage.
- Cyclone spam from wizard-heavy dominions to supplement military damage.
- Coordination on timing — military attacks drain morale, so attackers need rest periods.
- War declaration against the defending realm to enable both military attacks and Cyclone.

### For Defenders

A realm holding a wonder defends it by maintaining war status with attacking realms and mounting sufficient counter-military pressure. The controlling realm sees exact wonder HP, enabling them to assess whether a defense is sustainable. A wonder near zero power may not be worth defending — losing it while already owning another wonder returns it to neutral, which may be preferable to allowing the attacker to capture it with high contribution-scaled power.

### The One-Wonder Rule

The constraint that a realm cannot hold two wonders simultaneously creates natural wonder economy dynamics. Strong realms cannot monopolize all wonders. A realm that captures a second wonder while already owning one effectively gives the destroyed wonder away (to neutral or to whoever captures it next). This creates situations where it may be strategically correct to not capture a wonder, or to intentionally destroy an enemy wonder to neutral rather than trying to capture it.

### Planar Gates

This wonder has a unique qualitative effect rather than a percentage modifier: it allows the realm to "use" one additional technology from their unlocked pool. The design lets tech-heavy realms extract more value from their research investment by effectively sharing a tech bonus across all realm members.

---

## Interactions With Other Systems

- **[Races & Units](01-races-and-units.md)** — Wonder attack damage uses raw unit OP. Unit wonder-specific casualty reduction perks (`casualties_wonders` tech) are an exception to the fixed rate.
- **[Land & Construction](02-land-and-construction.md)** — Factory of Legends reduces construction costs realm-wide. School of War increases barracks housing.
- **[Population & Resources](03-population-and-resources.md)** — Multiple wonders boost production resources directly. Great Market improves employment and exchange rates. Fountain of Youth increases max population.
- **[Military](04-military.md)** — Wonder attacks consume morale and units (fixed casualty rate). Golden Throne boosts prestige from invasions. Ruby Monolith reduces both offensive and defensive casualties realm-wide.
- **[Magic](05-magic.md)** — Cyclone is the primary magical wonder damage tool. Great Oracle boosts wizard power and reduces spell costs. Ivory Tower and Wizard Academy provide defensive magic bonuses. Halls of Knowledge boosts research production.
- **[Espionage](06-espionage.md)** — Guild of Shadows boosts spy power and reduces spy losses. Underground Society provides defensive espionage protection. Imperial Armada prevents boat sabotage.
- **[Heroes](07-heroes.md)** — Hero perks (Blade of Sundering, Staff of the Stormcaller) amplify wonder attack damage. Altar of Heroes boosts hero bonuses and XP realm-wide (inactive).
- **[Technology](08-technology.md)** — Tech perks `wonder_damage` and `casualties_wonders` directly modify wonder attack effectiveness. Planar Gates shares a tech across the realm. Halls of Knowledge supplements School-based research production.
- **[Realms & Diplomacy](10-realms-and-diplomacy.md)** — War is required to attack owned wonders. The one-wonder-per-realm rule creates inter-realm competition. Wonder control is one of the primary objectives driving realm-vs-realm conflict.

---

## Player Decision Space

**When to assault a wonder** — Early-round wonders are weaker but contested by more motivated realms. Later wonders are stronger but may face fewer competitors. The round day fundamentally changes the cost-benefit of wonder assaults.

**Cyclone vs. military** — Cyclone deals more damage per action for wizard-heavy dominions and doesn't consume morale as aggressively as military attacks. Military attacks are necessary when wizard ratios are low. Most effective wonder campaigns use both.

**Contribution management** — Individual dominions want to hit the contribution threshold for prestige/mastery rewards without over-committing (as the fixed casualty rate makes wonder attacks expensive). Coordinating who attacks when is a realm communication challenge.

**Defend or concede** — A realm at low wonder HP must decide whether continued defense (declaring war, mounting counterattacks, using Cyclone on attackers) is worth the cost, or whether to let the wonder fall and focus resources on capturing the next available one.

**Target priority** — Not all wonders provide equally useful bonuses for a given realm composition. A realm with spy-heavy dominions may prioritize Guild of Shadows; an offense-focused realm may value Obelisk of Power or Golden Throne. Matching wonder targets to realm composition is a meaningful strategic layer.

> **Note:** The fixed 3.5% casualty rate on wonder attacks — unaffected by immortality, casualty reduction perks, or race abilities — means wonder assaults are always expensive. There is no way to "cheese" a wonder capture through clever unit selection. This design ensures wonders remain a genuine resource sink that only well-coordinated realms can contest efficiently.
