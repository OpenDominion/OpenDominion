# Land & Construction

## Overview

Land is the fundamental unit of dominion size. Everything in OpenDominion — population capacity, resource production, military power, and defensive strength — scales with how much land a dominion controls and how that land is developed. Construction converts raw, barren land into specialized buildings. Rezoning converts land from one terrain type to another. Together these systems form the economic foundation on which every other system builds.

---

## Core Concepts

**Acre** — The atomic unit of land. All land quantities are measured in acres.

**Land Type** — One of seven terrain categories. Each type supports a specific set of buildings.

**Barren Land** — Unconstucted acres. Available for building or rezoning. Lost first when invaded.

**Building** — A structure constructed on a single acre that provides production, housing, or a multiplier bonus.

**Construction Queue** — Buildings are not built instantly. They enter a queue and complete on the next game tick (hourly).

**Discounted Land** — Acres that can be rebuilt at a reduced cost, granted to a dominion after it loses land in combat.

**Rezoning** — Converting barren acres from one land type to another, at a platinum cost.

---

## Land Types

There are seven terrain types. Each race has a designated **home land** type (configured per race) that they begin with a natural supply of. Land type has no direct combat effect on its own, but determines which buildings can be placed there — and many unit perks scale with the percentage of a specific land type owned.

| Land Type | Supported Buildings |
|---|---|
| **Plain** | Home (race-dependent), Alchemy, Farm, Smithy, Masonry |
| **Mountain** | Ore Mine, Gryphon Nest |
| **Swamp** | Tower, Wizard Guild, Temple |
| **Cavern** | Diamond Mine, School |
| **Forest** | Lumberyard |
| **Hill** | Factory, Guard Tower, Shrine, Barracks |
| **Water** | Dock |

> **Note:** The Home building occupies the race's home land type specifically, not a fixed terrain. For a Forest race, Homes sit on Forest acres; for a Mountain race, on Mountain acres.

---

## Buildings

There are 18 building types. All buildings cost platinum and lumber to construct. Buildings provide one of three categories of benefit: **resource production**, **housing**, or a **percentage-based multiplier**.

### Housing Buildings

| Building | Land Type | Capacity |
|---|---|---|
| Home | Home (race-specific) | 30 peasants |
| Barracks | Hill | 36 military units |

Homes house the civilian population that generates taxes and provides the labor pool. Barracks house trained military units. Neither building employs peasants directly — they are pure housing.

### Resource Production Buildings

| Building | Land Type | Produces |
|---|---|---|
| Farm | Plain | Food (bushels per hour) |
| Alchemy | Plain | Platinum per hour |
| Ore Mine | Mountain | Ore per hour |
| Tower | Swamp | Mana per hour |
| Wizard Guild | Swamp | Mana per hour (small secondary amount) |
| Diamond Mine | Cavern | Gems per hour |
| School | Cavern | Research points per hour |
| Lumberyard | Forest | Lumber per hour |
| Dock | Water | Food per hour + boats over time |

**School scaling:** Schools have an inverse diminishing return — each additional school produces slightly less than the previous one, and they are capped at 50% of total land. This prevents pure research specialization from dominating.

**Dock dual output:** Docks produce both food (from fishing) and generate boat capacity over time. The boat generation rate scales with the round's progression day, rewarding early naval investment.

### Multiplier Buildings

These buildings do not produce resources but apply a percentage bonus to some game mechanic. All multiplier buildings scale **linearly** with their land percentage and are subject to a soft cap.

| Building | Land Type | Bonus | Cap |
|---|---|---|---|
| Smithy | Plain | Reduces military training costs | ~36% at ~18% land |
| Masonry | Plain | Increases castle improvement efficiency; reduces Lightning Bolt damage | ~50% at ~10% land |
| Gryphon Nest | Mountain | Increases offensive power | ~32% at ~20% land |
| Temple | Swamp | Increases population growth; reduces enemy defensive power | ~27% at ~20% land |
| Factory | Hill | Reduces construction and rezoning costs | ~50% at ~10% land |
| Guard Tower | Hill | Increases defensive power | ~32% at ~20% land |
| Shrine | Hill | Increases hero XP gain and hero ability bonus | ~200% at ~5% land |

The scaling formula for all multiplier buildings: `bonus = rate × (building_count / total_land)`, capped at the stated maximum. A dominion cannot break the cap regardless of how many of that building type it owns.

**Temple's dual effect** deserves special attention: it both grows the owner's population faster *and* reduces the defensive power of enemies. This makes Temple an offensive tool as well as an economic one.

**Shrine** has an unusually high cap, reflecting that hero bonuses are an important but narrow system — extreme shrine investment is possible but consumes a large share of Hill land that could otherwise house Guard Towers or Barracks.

---

## Construction

### Cost Structure

Every building costs **platinum** (primary) and **lumber** (secondary). Both costs scale upward as a dominion grows larger, creating natural diminishing returns on expansion.

The cost formula accounts for two distinct types of land gain:
- **Explored land** — land acquired through active exploration. Cheaper to develop.
- **Conquered land** — net land gained through invasion (total conquered minus total lost). More expensive to develop as a soft anti-snowball mechanism.

If a dominion has lost more land than it has conquered, conquered land contributes zero to the cost formula, meaning players recovering from a defeat pay exploration-tier prices.

**Factory buildings reduce both costs**, up to a hard cap. This creates a strong incentive to invest in Factories early — the cost savings compound over the entire round.

**Minimum cost floors** exist for both resources. No combination of discounts can reduce costs below a floor threshold, preventing free construction through stacking bonuses.

### Discounted Land

When a dominion loses land in combat, it receives a `discounted_land` credit equal to the acres lost. These acres can be rebuilt at a significant discount. The discount magnitude decreases as the round progresses, so recovering quickly after a loss is better than waiting.

This mechanic serves two design purposes:
1. Losing players can recover economically without being permanently set back.
2. It creates a mild incentive for attackers to spread hits rather than concentrating losses on one target (since one heavily-hit target can rebuild cheaply and return to size).

### Queue Mechanics

Construction is not immediate. When a player submits a build order:
1. The platinum and lumber are deducted immediately.
2. The buildings enter the **construction queue**.
3. The queue is processed during the next hourly game tick.
4. Buildings become active after the tick completes.

**Barren land accounting includes queued buildings.** A player cannot queue more buildings than they have barren acres, even if those buildings won't complete until next tick. This prevents accidentally over-building.

### Construction Flow

1. Validate building types are real and land types have sufficient barren acres.
2. Calculate total platinum and lumber cost (with all applicable discounts).
3. Verify the dominion can afford the order.
4. Deduct resources, enqueue buildings, log the event.

---

## Rezoning

Rezoning converts **barren** acres from one land type to another. It does not affect constructed buildings — only unconstucted acres can be rezoned.

### Cost

Rezoning costs platinum per acre. The formula scales similarly to construction: explored land is cheaper to rezone than conquered land. Factory reductions apply here as well.

### Conservation Rule

Rezoning is a strict land-type swap. The total acres removed from one or more source types must exactly equal the total acres added to destination types. Net land does not change — rezoning only changes the composition.

### Anti-Abuse Protection

Both rezoning and manual building destruction are subject to a **defensive power reduction limit**: a dominion cannot reduce its own DP by more than a threshold percentage within a 24-hour window. This prevents players from artificially collapsing their own defense to invite easy invasions (which would otherwise be exploitable for coordinated realm attacks).

---

## Building Destruction

Buildings can be removed in two ways:

### Manual Destruction

Players can demolish their own buildings via the Destroy action. The same DP reduction limit applies. Some tech and hero perks modify the outcome:
- **Destruction refund** — recover a portion of the construction cost in platinum or lumber.
- **Destruction discount** — destroyed buildings convert to discounted land credits.
- **Raze mod discount** — certain specific building types (e.g. Dock, Guard Tower) grant discounted acres when razed by a qualifying hero perk.

### Combat Destruction

When a dominion loses an invasion:
1. Land loss is calculated as a percentage of total land.
2. Barren land is destroyed first. Buildings are only destroyed if the required land loss cannot be met by barren land alone.
3. When buildings must be destroyed, they are chosen proportionally: buildings with higher total counts (including queued) lose more.
4. Queued buildings are destroyed before completed buildings.

This ordering means a land-heavy, lightly-built dominion absorbs losses gracefully (losing cheap barren land), while a densely-built dominion risks losing actual productive infrastructure.

---

## Land Size and Growth

A dominion begins with 250 acres. Growth happens through two mechanisms:

**Exploration** — Spending platinum to claim new wilderness. Explored land arrives in queue (there is a time delay). Exploration cost scales with current land size.

**Invasion** — Winning an invasion grants land from the target dominion. Land gained in combat arrives more quickly than explored land.

**Net land** (conquered minus lost) feeds back into construction and rezone costs. A dominion with a positive net conquest record pays slightly more to build, creating a natural brake on exponential military expansion.

---

## Interactions With Other Systems

- **[Races & Units](01-races-and-units.md)** — Unit perks scale with land type percentages. The home land type determines where Home buildings are placed. Race perks can reduce construction and rezone costs.
- **[Population & Resources](03-population-and-resources.md)** — Buildings are the primary source of resource production. Population housing (Homes) sets the cap on the tax base.
- **[Military](04-military.md)** — Gryphon Nests and Guard Towers are the primary military multiplier buildings. Barracks determine unit housing capacity. Land lost in combat triggers the building destruction algorithm.
- **[Magic](05-magic.md)** — Towers and Wizard Guilds produce mana and provide wizard protection. Masonry reduces Lightning Bolt damage. Temples reduce enemy defensive power.
- **[Heroes](07-heroes.md)** — Shrines amplify hero XP gain and hero ability bonuses.
- **[Technology](08-technology.md)** — Tech perks can reduce construction and rezone costs, and can modify destruction refunds.

---

## Player Decision Space

**Land type composition** is the central strategic question. Every acre spent on one land type is an acre not available for another. Key tradeoffs:

- **Hill vs. Mountain** — Guard Towers (defensive) vs. Gryphon Nests (offensive) vs. Barracks (housing). Hill is the most contested land type because it hosts four buildings.
- **Swamp investment** — Towers, Wizard Guilds, and Temples serve magic, protection, and anti-enemy-defense purposes. All three compete for the same swamp acres.
- **Factory priority** — Early Factory investment reduces construction and rezone costs for the entire round. Delaying Factories means paying full price longer.
- **School investment** — Research points fund long-term tech bonuses. Over-investing in Schools crowds out productive buildings.
- **Barren vs. built** — Keeping some land barren provides a buffer against land loss in combat (barren land is destroyed before buildings). Over-building leaves a dominion vulnerable to cascading building losses if invaded.

**Rezoning** creates flexibility: a dominion can adapt its land composition mid-round in response to threats or strategic shifts. The cost and DP-loss limits prevent abuse but allow genuine adaptation.

> **Note:** Building strategy is inseparable from race choice. A forest race with lumber production bonuses benefits disproportionately from Lumberyard investment; a mountain race naturally has more Mountain acres and should build Ore Mines or Gryphon Nests there. Optimal building ratios are race-specific.
