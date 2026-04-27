# Raids

## Overview

Raids are time-limited cooperative events in which all dominions in a round compete — and within realms, collaborate — against a shared environmental challenge. Unlike invasions, raids have no PvP dimension: there is no target dominion, no land changes hands, and no prestige is involved. Instead, raids ask dominions to contribute effort across multiple activity types (military, espionage, magic, exploration, resource investment, and hero combat) toward completing a set of objectives. When the raid ends, resources are distributed back to participants based on contribution.

Raids are a realm-coordination mechanism and a secondary resource economy. They reward consistent participation across the full range of game systems and give non-military-focused dominions (heavy mages, spymasters, investors) meaningful ways to contribute alongside combat specialists.

---

## Core Concepts

**Raid** — A named, time-bounded event with a pool of rewards and a set of objectives. Multiple raids can be defined for a round; each has its own start and end date.

**Objective** — A measurable milestone within a raid. Each objective has a `score_required` threshold; when the realm's total contributions to that objective reach it, the objective is completed. Objectives have their own date windows and can be gated differently within a raid.

**Tactic** — The specific action a dominion performs to contribute to an objective. Each objective defines one or more available tactics. Tactics are typed (invasion, espionage, magic, exploration, investment, hero) and have their own costs and scoring mechanics.

**Contribution** — A record of a single dominion's participation in a tactic. Contributions accumulate into a dominion's score, then into a realm score, then into the global raid score.

**Score** — The unit of measurement for raid participation. Every tactic produces score. Score is used to rank realms, rank players within realms, and calculate reward distribution.

**Realm Activity Multiplier** — A per-realm scaling factor applied to all scores. Realms with fewer active players relative to the round average receive a bonus multiplier; realms with more active players receive a penalty. This partially compensates for size and activity imbalances.

---

## Raid Structure

### Lifecycle

A raid moves through three states based on its dates:

| State | Condition |
|---|---|
| Upcoming | Current time is before `start_date` |
| In Progress | Current time is between `start_date` and `end_date` |
| Ended | Current time is after `end_date` |

Objectives follow the same lifecycle independently. A raid can be active while some of its objectives are still upcoming or already ended.

### Objectives

Each raid contains one or more objectives with individual `score_required` thresholds. An objective is **completed** when the sum of all realm contributions reaches that threshold.

The completion bonus at reward distribution scales with what fraction of objectives were completed: `(completed_objectives / total_objectives) × completion_reward_amount`. Partially completing a raid still yields partial completion bonuses.

### Rewards

Raids define two reward pools:

**Participation pool** — `reward_amount` in `reward_resource`. Distributed to all participating dominions using a two-tier allocation (see Reward Distribution below).

**Completion pool** — `completion_reward_amount` in `completion_reward_resource`. Distributed to each participating player based on objective completion percentage, regardless of individual score.

---

## Tactic Types

Each objective specifies which tactic types are available. A dominion chooses a tactic and pays its cost to generate score.

### Invasion Tactic

The dominion sends military units against the raid encounter. This is the only tactic that uses the military system but targets no player dominion.

**Costs:**
- Units suffer casualties at the rate defined in `attributes['casualties']`.
- 5 morale is deducted.

**Validation:** Standard military rules apply — the 40% home defense rule, the 5:4 rule, boat capacity, and morale sufficiency are all checked before the tactic is accepted. Units must have offensive power.

**Score:** Raw offensive power of units sent, multiplied by applicable tech perks (`raid_attack_damage`) and tactic bonuses, then by the realm activity multiplier.

**Return:** Surviving units return home after the normal invasion return delay.

### Espionage Tactic

The dominion commits spy resources to the raid objective.

**Costs:** Spy strength and morale, as defined by `attributes['strength_cost']` and `attributes['morale_cost']`.

**Score:** Base points (`attributes['points_awarded']`) × espionage score multiplier × tactic bonus multiplier × realm activity multiplier.

### Magic Tactic

The dominion commits wizard resources to the raid objective.

**Costs:** Wizard strength (`attributes['strength_cost']`) and mana. Mana cost is `attributes['mana_cost']` × total land (rounded up).

**Score:** Base points × magic score multiplier × tactic bonus multiplier × realm activity multiplier.

### Exploration Tactic

The dominion commits draftees and morale.

**Costs:** Draftees (`attributes['draftee_cost']`) and morale (`attributes['morale_cost']`).

**Score:** Base points × tactic bonus multiplier × realm activity multiplier.

### Investment Tactic

The dominion spends a resource directly (platinum, mana, ore, gems, etc.).

**Costs:** A fixed amount of a specified resource (`attributes['resource']` and `attributes['amount']`).

**Score:** Base points × tactic bonus multiplier × realm activity multiplier.

### Hero Tactic

The dominion's hero enters battle against a specific encounter defined in `attributes['encounter']`.

**Requirements:** The dominion must have a hero.

**Mechanics:** Initiates a hero battle (see [Heroes](07-heroes.md)). After the first realm victory against that encounter, subsequent battles against it become progressively easier:
- HP reduced by 10% per prior realm win (capped at 50% reduction).
- Evasion reduced by 10% per prior realm win (capped at 50% reduction).
- Encounter summon interval increases with each win.

Certain encounters adapt available hero actions based on the hero's class.

**Score:** Raw damage dealt in the encounter, multiplied by the realm activity multiplier. No additional bonus multiplier is applied to hero tactic scores.

---

## Score Calculation

Every tactic contribution produces a final score via:

```
Final Score = Base Points × Type Multiplier × Bonus Multiplier × Realm Activity Multiplier
```

**Base Points** — For invasion tactics: raw offensive power. For all others: `attributes['points_awarded']`.

**Type Multiplier** — Applies to espionage and magic tactics only, derived from the dominion's current espionage or magic score multiplier. Invasion, exploration, investment, and hero tactics use 1.0.

**Bonus Multiplier** — The single highest applicable bonus from these sources (they do not stack):
- **Race bonus** — specific races receive a percentage boost for specific tactic types.
- **Hero class bonus** — if the dominion's hero class matches the tactic's class requirement.
- **Alignment bonus** — based on the dominion's racial alignment.
- **Technology bonus** — unlocked tech perks that include raid bonuses.
- **Daily ranking bonus** — the #1 ranked dominion in a specific metric may receive a bonus for matching tactics.

**Realm Activity Multiplier** — Scales based on how active the dominion's realm is relative to the round average. The game tracks average active player count across all realms (updated each tick). Active is defined as: morale present, protection expired, not abandoned, not locked, and online within the last 18 hours.

| Realm Activity vs. Average | Score Multiplier |
|---|---|
| Below average | Bonus, up to 2.0× (capped) |
| At average | 1.0× |
| Above average | Penalty, down to 0.75× (capped) |

This multiplier is applied per contribution at submission time, not retroactively.

---

## Tactic Limits

Some tactics define a maximum number of times a single dominion may complete them (`attributes['limit']`). Once a dominion reaches the limit for a given tactic, that tactic is no longer available to them for that objective.

---

## Reward Distribution

When a raid ends, rewards are processed once (a `rewards_distributed` flag prevents double distribution). Distribution is two-tiered.

### Tier 1: Realm Allocation

The participation pool is divided among realms based on contribution:

1. Each realm's share is `realm_score / total_raid_score`.
2. Each realm's allocation is capped at **15% of the total participation pool**. Any realm whose proportional share exceeds 15% receives exactly 15%.
3. Any pool remaining after the per-realm cap is applied is divided equally among all participating realms.

This cap prevents a single dominant realm from claiming a disproportionate share.

### Tier 2: Player Allocation

Each realm's allocation is then distributed to individual dominions within that realm:

1. Each dominion's share is `dominion_score / realm_total_score`.
2. Each dominion's allocation is capped at **15% of the realm's allocation pool**. Same cap logic applies.
3. Remaining pool after per-player cap is divided equally among all participating players in the realm.

### Completion Bonus

The completion pool is distributed separately. Each participating player receives:

```
Player completion reward = (completed_objectives / total_objectives) × completion_reward_amount
```

This is a flat per-player award — it does not scale by individual score.

---

## Interactions With Other Systems

- **[Military](04-military.md)** — Invasion tactics use military units, morale, boats, and apply the 40% and 5:4 rules. Units suffer real casualties and return home after the normal delay. No land, prestige, or OP/DP outcome applies — there is no target dominion.
- **[Espionage](06-espionage.md)** — Espionage tactics consume spy strength and morale. The espionage score multiplier from the dominion's current spy ratio applies.
- **[Magic](05-magic.md)** — Magic tactics consume wizard strength and mana. The magic score multiplier from the dominion's wizard ratio applies.
- **[Heroes](07-heroes.md)** — Hero tactics initiate hero battles. Hero XP is awarded from encounter victories. Encounter difficulty scales down as the realm defeats it repeatedly.
- **[Technology](08-technology.md)** — Tech perks can include `raid_attack_damage` multipliers (amplifying invasion tactic scores) and tactic-specific bonus multipliers.
- **[Races & Units](01-races-and-units.md)** — Race alignment and specific race identity feed into tactic bonus multipliers. Invasion tactics use all standard unit perks for OP calculation.
- **[Realms & Diplomacy](10-realms-and-diplomacy.md)** — Raid progress and leaderboard standings are visible at the realm level. The realm activity multiplier directly connects realm participation rates to individual score outcomes.

---

## Player Decision Space

**Tactic selection** — Most objectives offer multiple tactic types. A dominion should pick the tactic that best leverages its current strengths: a wizard-heavy dominion benefits from magic tactics (higher multiplier from wizard ratio); an invasion-focused dominion maximizes score through raw OP.

**Resource allocation vs. raid participation** — Investment tactics consume resources directly; espionage and magic tactics consume operational stamina (spy/wizard strength) that regenerates slowly. Participating heavily in a raid has real opportunity costs against other game actions.

**Timing within objective windows** — Each objective has its own date window. Contributing to an objective far from its threshold may be wasted if the realm cannot reach completion. Concentrating effort on objectives near their threshold secures completion bonuses more efficiently.

**Pacing around limits** — Tactics with per-dominion limits should be used strategically. Burning the limit early on a tactic where the dominion has a matching bonus (race, hero class, tech) is usually correct.

**Coordinating within the realm** — Because rewards are realm-allocated first and then re-distributed within the realm, intra-realm coordination matters. A realm where dominions contribute across complementary tactic types reaches objectives faster and distributes reward more equitably than one where contributions cluster on a single tactic.

> **Note:** Raid invasion tactics do not interact with the prestige, land, or morale invasion threshold of the main invasion system. The 5 morale cost for an invasion tactic is independent of the morale minimum required to invade another dominion — a dominion locked out of PvP invasions by low morale can still use invasion tactics in raids, as long as 5 morale is available.
