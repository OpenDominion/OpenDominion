# Heroes

## Overview

Each dominion fields a single persistent hero that gains experience through gameplay and levels up over the course of a round. Heroes provide passive bonuses to their domain — economic, military, magical, or espionage — that scale linearly with level. As heroes level up, they unlock permanent upgrades that modify their base bonuses, XP gain patterns, and battle behavior. The hero system rewards sustained active play and creates meaningful long-term decisions around class selection and upgrade pathing.

---

## Core Concepts

**Hero Class** — Determines the passive bonus type and coefficient. A hero's class can be changed, but a cooldown and XP penalties apply.

**Hero Level** — Increases with accumulated XP up to a maximum. Higher level = larger passive bonus.

**Hero Upgrade** — A permanent ability or modifier unlocked at specific levels. Upgrades never reset, even when changing classes.

**Shrine** — The building (Hill land type) that amplifies both XP gain rate and passive bonus strength. Investment in Shrines is the primary way to accelerate hero progression.

**Class Change Cooldown** — Prevents rapid class-switching. A hero cannot change class again for several days after switching.

**Inactive Bonus** — When not actively selected, a hero class still provides half of its normal bonus from accumulated XP in that class.

---

## Hero Creation

Each dominion creates one hero at the start of the round. The hero's name is drawn from a race-specific pool (each race has hundreds of available names). Only one hero can exist per dominion at a time. The hero's class is selected on creation from the available basic classes.

---

## Hero Classes

Heroes have two tiers of classes: **basic** (available to all from the start) and **advanced** (unlocked by meeting gameplay prerequisites).

### Basic Classes

| Class | Passive Bonus | Notes |
|---|---|---|
| Alchemist | Platinum production | Economic income boost |
| Architect | Construction cost reduction | Cheaper building |
| Blacksmith | Military training cost reduction | Cheaper units |
| Engineer | Castle investment efficiency | More output per resource invested |
| Farmer | Food production | Sustains larger populations |
| Healer | Casualty reduction | Preserves military units |
| Infiltrator | Spy power | Espionage effectiveness |
| Sorcerer | Wizard power | Magic effectiveness |

All basic class bonuses scale linearly: each level adds the class coefficient to the total multiplier. Coefficients vary — Infiltrator and Sorcerer have the highest raw coefficient per level, reflecting that spy and wizard power are high-value multipliers; Blacksmith has the lowest because training cost reduction is always relevant and compounds across many units.

### Advanced Classes

Advanced classes unlock only after meeting a specific gameplay threshold, providing a mechanism where different playstyles — economic, aggressive, expansive — can access specialized bonuses.

**Scholar** — Requires a minimum number of technologies researched. Provides max population bonus per level. At unlock, automatically gains the **Pursuit of Knowledge** directive: a significant tech production bonus at the cost of reduced castle investment efficiency. This class rewards dominions that invest heavily in the tech tree early.

**Scion** — Requires a minimum number of successful invasions. Provides exploration cost reduction per level. At unlock, automatically gains a set of directives with major strategic implications:
- **Disarmament** — completely removes offensive power in exchange for a building destruction discount. This converts the hero into a support/economic tool rather than a combat multiplier.
- **Martyrdom** — provides a prestige-based cost reduction to spies, wizards, and their promoted forms. Scales with total prestige accumulated; dominant players gain more efficient unit training.
- **Revised Strategy** — a one-time immediate effect that refunds a portion of unspent tech points, allowing a pivot in the technology tree.

The Scion's directives are some of the most powerful single unlocks in the game. They create genuine commitment decisions — Disarmament in particular is irreversible in character, trading the hero's entire combat contribution for economic gains.

---

## Experience and Leveling

### XP Sources

| Activity | Base XP | Notes |
|---|---|---|
| Successful invasion (≥75% range) | Per acre gained | Primary XP source for military players |
| Exploration | Per acre explored | Less than invasion, but consistent |
| Successful spy operation | Per spy strength consumed | Excludes theft operations |
| Successful spell cast | Per wizard strength consumed | Offensive and info casts |

XP from invasion and exploration is amplified by **Shrine buildings** — each Shrine as a percentage of total land adds to a bonus multiplier, up to a substantial cap. This means shrine-heavy dominions level heroes dramatically faster than shrine-light ones. Shrines are therefore both a hero investment and a competitive differentiator.

Racial bonuses (`hero_experience` perk) and wonder bonuses can further multiply XP gain.

### Doctrine Perks and XP Specialization

At Level 1, heroes unlock a **Doctrine** upgrade. Doctrines permanently reshape XP gain:

- **Lead from the Front** — massively increases invasion XP, but blocks all XP from spy and magic operations. For purely military heroes.
- **Lead from the Shadows** — massively increases spy/magic operation XP. For magic/espionage-focused heroes.

Choosing a doctrine commits the hero to a primary XP source. A military player who switches to espionage after taking Lead from the Front gains nothing from their spy ops.

### Level Table

The XP required per level increases with progression. Early levels are fast to reach; the final levels require sustained investment. Maximum level is 12.

The progression is roughly linear at low levels and steepens significantly toward the cap. The steepening incentivizes investing in Shrines early: a dominion that delays shrine construction pays a compounding cost in XP time.

### XP Loss

Successful invasion defense against the hero's dominion causes the hero to lose XP proportional to land lost. XP loss is capped so the hero cannot fall below the minimum XP for their current level — levels are never lost, only progress within the current level is reversed. This prevents a hero from being catastrophically set back by a single attack.

### Inactive Class XP

Each class stores XP independently. When switching classes, XP in the old class is preserved. XP in the new class resumes from where it was left off. The inactive class's passive bonus is applied at 50% of its normal strength — not zero — providing a secondary benefit for players who have leveled multiple classes before switching.

When switching, current-class XP is capped at the minimum for the current level (excess progress is lost). This creates a real cost to switching: a hero near level 8 who switches forfeits progress toward that level.

---

## Upgrades

Heroes unlock one upgrade at each even level (2, 4, 6) plus the Doctrine at Level 1. Upgrades are **permanent** — they persist through class changes and cannot be reset. This means upgrade choices made early have lasting consequences throughout the round.

### Upgrade Categories

**Doctrines (Level 1)** — Reshape XP gain and grant combat stats. Chosen once at Level 1 and define the hero's XP path for the round.

**Magic School Upgrades (Levels 2, 4, 6)** — Spell-related bonuses (damage boosts, cost reductions, duration penalties to enemies):
- Abjuration: Reduces incoming Lightning Bolt damage.
- Divination: Reduces info spell mana cost.
- Enchantment: Reduces wizard strength cost for self spells.
- Evocation: Increases Fireball damage.
- Illusion: Hides identity on failed spy operations.
- Transmutation: Enables mana exchange (converting mana to other resources or vice versa).

**Item Upgrades (Levels 2, 4, 6)** — Equipment-based bonuses across multiple systems:
- King's Banner: Reduces morale loss from invasions.
- Spyglass: Reduces spy strength cost for land scouting operations.
- Scribe's Journal: Increases tech production from invasions.
- Orb of Detection: Increases spy losses inflicted on enemies who fail operations against you.
- Blade of Sundering: Increases wonder attack damage from Cyclone.
- Staff of the Stormcaller: Increases Cyclone damage.
- Anti-Magic Sigils: Reduces duration of enemy spells applied to the dominion.

**Directives (Advanced classes only)** — Auto-unlock at class selection, often with dramatic or irreversible effects.

### Upgrade Interactions

Upgrades compound with other systems. Examples:
- Evocation + high Spires investment = strong Fireball output.
- Illusion + covert espionage strategy = anonymous spy campaigns.
- King's Banner + aggressive raiding playstyle = sustained morale management.
- Anti-Magic Sigils + Energy Mirror = layered spell duration defense.

---

## Passive Bonus Application

The hero's passive bonus is applied as a multiplier to the relevant game system:

```
Final Bonus = Class Coefficient × Level × Passive Bonus Multiplier

Passive Bonus Multiplier = 1 + Shrine Bonus + Race Bonus + Wonder Bonus
```

Where Shrine Bonus scales with (shrine buildings / total land), capped at a maximum percentage. The cap is generous — a heavily shrine-invested dominion can more than double their hero's effective bonus.

Upgrade perks add to or override portions of this formula depending on the perk type.

---

## Hero Combat

Heroes can engage in direct combat in certain game contexts (primarily wonder attacks, though the system is architecture for broader use). Combat is a turn-based sequence where both heroes take alternating actions.

### Combat Stats

Base combat stats scale with level (health grows with each level; other stats are fixed base values). Upgrade perks contribute additional stats:
- `combat_health` — bonus HP.
- `combat_attack` — bonus attack damage.
- `combat_defense` — bonus damage reduction.
- `combat_evasion` — chance to avoid attacks.
- `combat_focus` — bonus damage on focus actions.
- `combat_counter` — bonus damage on counter actions.
- `combat_recover` — healing on recover actions.

### Combat Actions

**Universal actions:**
- **Attack** — standard damage.
- **Defend** — doubles defense for the turn.
- **Focus** — powers up the next attack.
- **Counter** — prepares a counter-attack.
- **Recover** — heals by the recover stat amount.

**Class-specific actions** (selected by class, one per class):
- Alchemist: Volatile Mixture — high damage, partial backfire risk.
- Architect: Fortify — applies a damage absorption shield.
- Blacksmith: Forge — permanently increases attack for the rest of combat.
- Engineer: Demolish — area attack bypassing standard defenses.
- Farmer: Hardiness — survive one killing blow at 1 HP.
- Healer: Mending — enhanced recovery on focus turns.
- Infiltrator: Shadow Strike — unavoidable attack.
- Sorcerer: Crushing Blow — bonus damage against non-defending targets.
- Scholar: Combat Analysis — permanently reduces enemy defense.
- Scion: Last Stand — boosts all stats at low HP.

The no-repeated-action rule prevents degenerate strategies (e.g., spamming Focus or Counter) and forces varied action selection within a combat session.

### Combat Abilities

Certain upgrades grant passive combat abilities that activate automatically:
- **Undying** — return from defeat once per combat.
- **Lifesteal** — heal for a portion of damage dealt.
- **Enrage** — increased attack at low HP.
- **Elusive** — take zero damage from evaded attacks.
- **Retribution** — bonus counter damage.

These abilities can make lower-level heroes competitive against higher-level ones through tactical advantage.

---

## Class Change Strategy

The cooldown between class changes (several days) combined with the XP cap penalty creates genuine long-term commitment to a class path. Players must weigh:

- **Staying in one class** — maximizes passive bonus for that class through the full level range.
- **Switching classes** — accesses a different passive bonus category and accumulates upgrades in a new class, but loses current-level progress and pays the cooldown cost.
- **Multi-class banking** — intentionally switching and back to accumulate upgrades in multiple classes, accepting the 50% inactive penalty.

The permanent nature of upgrades means switching can be worthwhile purely for the upgrade unlocked, even at the cost of lost class progression. A player might switch to Sorcerer at Level 2 specifically to unlock Evocation, then switch back.

---

## Interactions With Other Systems

- **[Land & Construction](02-land-and-construction.md)** — Shrine buildings amplify both XP gain and passive bonus multiplier. Hero construction cost perks (Architect) affect building cost. Hero raze perks affect building destruction outcomes.
- **[Population & Resources](03-population-and-resources.md)** — Alchemist, Farmer, and Engineer heroes directly modify production. Scholar increases max population. Hero perks can affect castle investment efficiency.
- **[Military](04-military.md)** — Heroes gain XP from invasions. Healer reduces casualties. Blacksmith reduces training costs. Morale perks (King's Banner) reduce morale loss from attacks. Hero offensive power bonus (if applicable) feeds into the OP multiplier.
- **[Magic](05-magic.md)** — Sorcerer boosts wizard power. Enchantment reduces self-spell strength cost. Evocation boosts Fireball damage. Anti-Magic Sigils reduce hostile spell duration. Hero perks feed into spell damage multipliers.
- **[Espionage](06-espionage.md)** — Infiltrator boosts spy power. Illusion hides identity on failure. Orb of Detection amplifies spy losses inflicted. Spyglass reduces info op strength cost. Heroes gain XP from successful spy operations.
- **[Technology](08-technology.md)** — Scholar requires and benefits from tech investment. Scribe's Journal increases tech production from invasions. Revised Strategy directive enables tech pivoting.
- **[Wonders](09-wonders.md)** — Blade of Sundering and Staff of the Stormcaller amplify wonder attack damage. Hero XP gain can be boosted by wonder perks.

---

## Player Decision Space

**Class selection** — The most impactful hero decision, setting the passive bonus direction for the round. Military-focused players typically choose Healer or Blacksmith early; magic players choose Sorcerer; spy players choose Infiltrator. Economic specialists may prefer Alchemist or Farmer.

**Shrine investment** — How much Hill land to dedicate to Shrines versus Guard Towers or Barracks. Heavy shrine investment dramatically accelerates hero leveling but reduces military capacity. Shrine-heavy builds are particularly valuable if the hero's passive bonus has a high coefficient (Sorcerer, Infiltrator).

**Doctrine choice at Level 1** — Commits the hero's XP path. This cannot be undone. A military player who casts spells occasionally might find Lead from the Front suboptimal. Evaluate honestly where XP will actually come from.

**Upgrade selection at even levels** — Each upgrade is permanent. Choosing combat-oriented upgrades (staff, blade) favors wonder attack participation. Choosing economic upgrades (scribe's journal, enchantment) favors long-term resource efficiency. The choice should align with the dominion's intended role in the realm.

**Advanced class timing** — Scholar and Scion require prerequisites. A player aiming for Scholar should prioritize early tech research to unlock the class while levels can still be gained; a player aiming for Scion should attack early. Waiting too long to qualify for an advanced class reduces the window to benefit from it.

> **Note:** The Scion's Disarmament directive is one of the most unusual mechanics in the game — a hero that literally removes the dominion's offensive military power in exchange for economic benefits. This creates a legitimate "economic support" archetype where a dominion explicitly opts out of attacking in favor of pure production and building optimization.
