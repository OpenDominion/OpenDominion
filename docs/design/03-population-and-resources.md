# Population & Resources

## Overview

Population and resources are the economic engine of OpenDominion. Every action in the game — training units, casting spells, constructing buildings, researching technology — consumes resources produced by the civilian economy. Population determines how much platinum is taxed, how many units can be trained, and how fast a dominion can grow. Resources are produced each hourly tick, consumed by ongoing costs, and decay if left unspent. Managing the balance between growth, production, and consumption is the core economic challenge of every round.

---

## Core Concepts

**Peasant** — A civilian who pays taxes, occupies housing, and can be drafted into the military. The primary source of platinum income.

**Draftee** — A peasant in the process of being converted to military service. Spends one tick in transit before joining the army. Controlled by the player's draft rate setting.

**Max Population** — The total number of beings (peasants + all military) the dominion can support. Set by housing, prestige, and race/tech/wonder bonuses.

**Employment** — The fraction of peasants who have jobs in production buildings. Only employed peasants generate their full tax contribution. Idle peasants reduce effective income.

**Morale** — A 0–100 score representing dominion stability. Affects military power and prestige gain rates. Recovers passively over time; is drained by actions like exploration.

**Prestige** — A cumulative score earned primarily through invasions. Acts as a passive multiplier on food production and maximum population, and scales certain unit perks.

**Castle Improvements** — Six permanent investment tracks (Science, Keep, Forges, Walls, Spires, Harbor) that convert resources into lasting multiplier bonuses.

---

## Population

### Maximum Population

Max population determines the ceiling on total beings. It is calculated in three parts:

**Housing from buildings:**
- Each **Home** provides housing for a fixed number of peasants.
- Each **non-home, non-barracks building** provides a smaller amount of housing. This means every building contributes to population capacity regardless of its primary purpose — an Alchemy or Factory also houses people.
- Buildings in the construction queue count toward housing capacity immediately, even before they complete.
- Each **barren acre** provides a small amount of population capacity, modified by race perks, technology, and wonders.

**Population multiplier:**
A composite multiplier applied to the raw housing total, drawn from:
- Race perks (positive or negative)
- Keep castle improvement
- Technology bonuses
- Wonder bonuses
- Hero perks
- Prestige: contributes a small multiplicative bonus proportional to accumulated prestige

**Military housing bonus:**
Barracks provide additional capacity specifically for trained military units (not peasants). The capacity per barracks can be increased by race perks, technology, and wonder bonuses. Certain unit perks also provide self-contained housing for units of that type.

### Peasant Population

Peasants grow each tick through natural birth. The birth rate is a percentage of the current peasant count, modified by:
- Starvation: if food reaches zero, birth rate drops to zero immediately.
- Race perks (population growth modifier)
- Technology and spell bonuses
- Temple buildings: each percentage point of land occupied by Temples adds to the population growth multiplier.

Growth is constrained by available headroom: if the dominion is near its population cap, excess births are capped by available space.

### Draft Rate and Draftees

The **draft rate** is a player-controlled setting (0–100%) representing the target ratio of military-to-total population. Each tick, if the current military percentage is below the target draft rate, a fraction of peasants convert into draftees. Draftees spend one tick in transit before joining the standing army.

Lowering the draft rate causes peasants to accumulate faster (more tax base, more growth). Raising it accelerates military growth at the cost of reduced civilian population. This is one of the most impactful micro-decisions in the game, especially during rapid expansion phases.

### Employment

Every production building (everything except Homes and Barracks) employs a fixed number of peasants. If total jobs exceed total peasants, all peasants are employed at 100%. If peasants exceed available jobs, the surplus sits idle — paying reduced taxes.

**Employment percentage** directly affects platinum production efficiency. A dominion with many more peasants than buildings will under-perform economically until more production buildings are constructed.

The design tension: Homes maximize peasant count but provide no jobs. Production buildings provide jobs and secondary housing. The optimal ratio depends on available land and current growth phase.

### Morale

Morale is a 0–100 integer. It recovers passively each tick:
- At low morale: recovers faster per tick.
- At high morale: recovers more slowly (fine-tuning near the cap).
- Hard cap at 100.

**Effect on military:** Morale applies a penalty to both offensive and defensive power. At full morale there is no penalty. At zero morale, both OP and DP are meaningfully reduced. The penalty scales linearly between the two extremes.

**Effect on prestige gain:** Low morale also reduces the prestige earned from successful invasions. A dominion that spams actions and drains morale earns less prestige per attack.

**What drains morale:**
- Exploration actions (each exploration lowers morale)
- Certain combat outcomes

Because morale affects combat power, aggressive players must pace exploration and attacks to avoid fighting at reduced effectiveness.

---

## Resources

Eight resources are tracked per dominion. All change once per hour at the game tick.

### Platinum

The primary currency. Used for: training military units, exploration, construction, rezoning, and most other actions.

**Production sources:**
- **Employed peasants** — each employed peasant generates a fixed platinum tax per tick. This is the dominant source of income for most dominions and scales directly with population size and employment rate.
- **Alchemy buildings** — each Alchemy generates a flat platinum amount per tick. The Alchemist Flame spell boosts per-Alchemy output.

**Production multipliers:** Race perk, technology, wonders, spells, hero perks, and the Science castle improvement all add to a composite platinum multiplier. The multiplier is soft-capped to prevent extreme stacking.

**Guard tax:** Dominions that join the Royal Guard or Elite Guard (realm-level organizations) pay a small platinum tax, reducing net production.

**No decay.** Platinum accumulates indefinitely.

### Food

Required to sustain the population. Starvation stops population growth and can cause deaths.

**Production sources:**
- **Farm buildings** — primary food source, producing a fixed amount per farm per tick.
- **Dock buildings** — secondary food source (fishing), producing a smaller flat amount per dock.
- Harbor castle improvement increases dock food output.

**Consumption:** Every living being (peasant and military) consumes a fixed amount of food per tick. Total consumption scales directly with total population. Races with food consumption reduction perks sustain larger militaries at lower food cost.

**Decay:** Food decays at a small percentage per tick. Stockpiling food is partially self-defeating — excess beyond near-term consumption is gradually lost.

**Net food** = Production − Consumption − Decay. Negative net food depletes the stockpile; when the stockpile reaches zero, population growth halts and units may begin dying.

**Prestige bonus:** Prestige provides a multiplicative bonus to food production, making high-prestige dominions more food-self-sufficient.

### Lumber

A construction material. Used for: building construction and some unit training costs.

**Production sources:**
- **Lumberyard buildings** — primary source.
- Some unit perks generate lumber passively (e.g., Wood Elf Wisp produces lumber based on forest land percentage).

**Decay:** Lumber decays at a small percentage per tick. Like food, excess lumber gradually erodes.

**Net lumber** = Production − Decay. Lumber is not consumed by ongoing costs (only by explicit build orders), so it accumulates between construction projects.

### Ore

A military material. Used for: training certain unit types and castle investment.

**Production sources:**
- **Ore Mine buildings** — primary source.
- Some unit perks generate ore passively (e.g., Dwarf Miner units produce ore proportional to their count).

**No decay.** Ore accumulates indefinitely.

### Mana

The fuel for the magic system. Spell casting consumes mana; insufficient mana prevents spell use.

**Production sources:**
- **Tower buildings** — primary source.
- **Wizard Guild buildings** — secondary, smaller source per building; additionally provides wizard protection.
- Spires castle improvement boosts mana production and provides spell defense.
- Technology can increase per-Tower output and add wartime mana bonuses (extra mana while at war).

**Decay:** Mana decays at twice the rate of food and lumber. This is the fastest-decaying resource, discouraging large mana stockpiles and rewarding active spell use.

**Net mana** = Production − Decay. Active magic users need sustained investment in Towers to maintain spell output.

### Gems

A rare resource. Used exclusively for castle investment (at high exchange value per point) and training a small number of unit types.

**Production sources:**
- **Diamond Mine buildings** — only source.

**No decay.** Gems accumulate indefinitely. Because Diamond Mines compete with other Cavern buildings (Schools), gem production requires deliberate land allocation.

### Research Points (Tech)

Used to unlock technology upgrades on the tech tree.

**Production sources:**
- **School buildings** — the sole source. Each school generates research points per tick.
- Schools have an inverse diminishing return: output per school decreases as the proportion of land dedicated to Schools increases.
- Schools are capped at 50% of total land — beyond that, additional schools produce no output.
- Race perks, hero perks, wonder bonuses, and invasion success (for some races) add multiplicative bonuses to total research point production.

**No decay.** Research points accumulate until spent.

### Boats

Provide transport capacity for invading forces. Units that require boats cannot be sent on invasions beyond available boat capacity.

**Production:**
- **Dock buildings** generate boats slowly over time. The accumulation rate scales with how far into the round the game is, rewarding early dock investment.
- Harbor castle improvement increases boat generation.

**No decay.** Boats accumulate. Some unit abilities (Merfolk Kraken, Leviathan) can destroy enemy boats during combat.

---

## Castle Improvements

Castle improvements are a parallel investment system distinct from buildings. Resources are converted into permanent improvement points, which translate into multiplier bonuses via a diminishing-return curve.

The formula is asymptotic: early investment gives large gains; further investment gives smaller incremental gains. The improvement's coefficient determines how quickly the curve flattens. **Masonry buildings** improve the efficiency of all castle improvements simultaneously, making Masonry investment valuable for any dominion that plans heavy castle spending.

### The Six Improvements

**Science** — Increases platinum production. Best-in-class economic investment early in the round when platinum is the binding constraint.

**Keep** — Increases maximum population. Enables more peasants (more tax income) and more military units. Synergizes with population-heavy races.

**Forges** — Increases offensive power. A multiplier on top of Gryphon Nests, race perks, and unit power. Highly valuable for attacker-archetype races.

**Walls** — Increases defensive power. Equivalent to Forges for defenders. Stacks with Guard Towers and race DP bonuses.

**Spires** — Increases offensive wizard power and mana production. Also provides spell protection — reduces damage from hostile spells targeting the dominion. The dual offensive-and-defensive function makes Spires valuable for any magic-active dominion.

**Harbor** — Increases food and boat production. The food boost is meaningful for high-population dominions; the boat boost is critical for navally-dependent races sending large invasion forces.

**Investment resources:** Platinum, ore, lumber, and gems can all be converted into improvement points at fixed exchange rates. Gems are the most point-efficient per resource unit but are scarce. Platinum and ore are the most commonly used inputs.

---

## Networth

Networth is a single score summarizing a dominion's total strength. It is used for matchmaking, ranking, and determining invasion eligibility ranges.

**Components:**
- Each acre of land contributes a fixed amount.
- Each building contributes a small amount.
- Specialist units (slots 1–2, spies, assassins, wizards, archmages) each contribute a fixed amount.
- Elite units (slots 3–4) contribute an amount proportional to their maximum power stat (offense or defense, whichever is higher).

Because elite units scale by power stat, high-OP and high-DP elites contribute more networth than cheaper specialists. This means a dominion with strong but expensive elites appears larger and is matched against proportionally larger opponents.

Networth is recalculated and cached periodically rather than computed live on every request.

---

## Interactions With Other Systems

- **[Races & Units](01-races-and-units.md)** — Race perks set all production and consumption modifiers. Unit perks can produce resources passively. Food consumption rates affect how large a military is sustainable.
- **[Land & Construction](02-land-and-construction.md)** — Buildings are the primary production infrastructure. Land composition determines which buildings can be placed. Construction costs platinum and lumber; castle investment costs all resource types.
- **[Military](04-military.md)** — Training costs platinum and secondary resources. Morale affects combat power. Draftees are the bridge between civilian and military population.
- **[Magic](05-magic.md)** — Mana production and decay govern the cadence of spell casting. Spires castle improvement buffs wizard power and protects against enemy spells.
- **[Espionage](06-espionage.md)** — Spy training costs platinum. Some black ops steal resources directly.
- **[Heroes](07-heroes.md)** — Some hero perks modify production multipliers or castle improvement efficiency.
- **[Technology](08-technology.md)** — Tech unlocks add production multipliers across all resource types and can reduce consumption.
- **[Wonders](09-wonders.md)** — Wonders apply realm-wide production multipliers and can modify employment, population caps, and resource output.

---

## Player Decision Space

**Draft rate** — The single most impactful ongoing setting. Higher draft rate grows the military faster but shrinks the tax base. Optimal draft rate varies by phase: low during expansion (maximize income), higher when preparing to fight.

**Employment balance** — Choosing between Homes (peasant growth) and production buildings (income and jobs). A dominion that over-builds Homes without jobs produces little; one that under-builds Homes caps population and income unnecessarily.

**Castle investment priority** — Science vs. Keep vs. Forges/Walls vs. Spires vs. Harbor. The right priority depends on race, playstyle, and what stage of the round the dominion is in. Early Science is often universally strong; Forges or Walls become priorities later.

**Food management** — Races with high food consumption must invest heavily in Farms or Docks. Starvation is a hard stop on population growth. The 1% per tick decay means excess food cannot be stockpiled efficiently — production should be tuned close to consumption.

**Mana discipline** — Mana's 2% decay rate is punishing. Dominions that build Towers without actively casting spells waste a large fraction of output. Mana investment should match intended spell frequency.

**Resource hoarding vs. spending** — Platinum has no decay and can be stockpiled indefinitely for large spending events (a wave of unit training, a major construction push). Lumber and food decay slowly; ore and gems do not decay. Knowing which resources to spend aggressively versus which to bank is a meaningful economic skill.

> **Note:** Employment percentage is a key diagnostic. A dominion at 100% employment is peasant-limited and should build more Homes or Keep investment. A dominion below 100% employment is building-limited and should add production buildings. Watching this ratio guides efficient expansion decisions throughout the round.
