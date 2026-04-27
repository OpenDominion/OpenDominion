# Round Structure

## Overview

A round is a complete season of OpenDominion — a defined period with a start date, an end date, and a fixed set of rules. All gameplay takes place within rounds. Rounds are independent: dominions, heroes, technologies, and land do not carry over between rounds. Each new round is a fresh start for every player. The game advances in hourly increments called ticks, with a daily reset layered on top. Understanding the temporal structure of a round — when things happen, how actions compound over time, and what changes as the round ages — is essential to effective play.

---

## Core Concepts

**Round** — A complete play season with defined start, end, rules, and participants.

**Tick** — The hourly game update. All production, population growth, queue advancement, spell durations, and stat changes occur once per tick.

**Daily Reset** — A once-per-day event (at the same hour the round started) that resets daily action limits and triggers daily operations like inactive player management and wonder spawning.

**Protection** — A period at the start of each dominion's life during which it cannot be attacked and cannot take offensive actions. Provides time to build economic foundations.

**Round League** — The ruleset variant applied to a round. Determines specific mechanical configurations that differentiate round types.

**Graveyard** — Realm 0, the holding area for inactive or unassigned dominions.

---

## Round Lifecycle

### Pre-Round: Registration

Players register for an upcoming round individually or as part of a pack (up to 4 players). During registration, players select their race and optionally form or join a pack with known teammates.

Registration remains open until **96 hours before round start**, when the realm assignment process runs. After assignment, late-registering players can still join the round but are placed into existing realms rather than being part of the original assignment optimization.

### Realm Assignment (T-96 hours)

The realm assignment algorithm runs once, distributing all registered players into their realms. See [Realms & Diplomacy](10-realms-and-diplomacy.md) for the full algorithm. After assignment, the realm structure is locked — players cannot change realms.

### Round Start

At the start date, the round becomes active. All dominions enter their protection period simultaneously. NPC (bot) dominions are also spawned at this point to fill out the player ecosystem.

### Active Round

The bulk of the round. Players manage their dominions tick by tick, competing for land, wonders, prestige, and ultimately networth ranking. The round proceeds for its configured duration (typically several weeks).

**Offensive Actions Prohibited window** — Near the round's end, offensive actions (invasions, spy ops, hostile spells) are disabled. This prevents late-round suicide attacks where a losing player destroys their dominion to harm a leading player. The prohibition activates at a configured timestamp before the end date.

### Round End

At the end date, the round closes. Final rankings are calculated. The round enters a read-only historical state.

---

## The Hourly Tick

Every hour, on the hour, the game engine processes all active dominions. This is the heartbeat of OpenDominion. Players do not need to be online during ticks — the game runs autonomously.

### Tick Execution Order

1. **Daily reset check** — If the current hour matches the round's start hour, daily bonuses reset and daily operations run.

2. **Pre-calculated deltas applied** — Each dominion has a pre-calculated "next tick" snapshot stored from the previous tick's end. This snapshot is applied atomically:
   - Prestige adjusted
   - Peasant population updated
   - Morale updated
   - Spy and wizard strength updated
   - Resilience and spell meters updated
   - All resources updated (platinum, food, lumber, mana, ore, gems, research points, boats)
   - Castle improvements updated
   - Military units updated (draftees, unit slots 1–4, spies, assassins, wizards, archmages)
   - Land updated by type
   - Buildings updated (all types, constructed and queued)

3. **Queue advancement** — All queued actions (construction, training, exploration, returning invasion forces, returning land, returning prestige) have their countdown decremented by 1 hour. Items reaching 0 hours complete and transfer to active state.

4. **Spell duration decrement** — Active spells have their remaining duration reduced by 1 hour. Spells reaching 0 are removed.

5. **Expired war cleanup** — Wars that have exceeded their maximum duration are auto-cancelled.

6. **Hero and arena processing** — Hero battle results and tournament progressions are processed.

7. **Raid processing** — Completed raids distribute their rewards.

8. **Next-tick pre-calculation** — After applying the current tick, the engine immediately calculates what will happen next tick for every dominion and stores the result. This includes:
   - Population growth (peasants and draftees)
   - All resource production, consumption, and decay
   - Spy and wizard strength recovery
   - Starvation casualties (if food will hit zero)
   - Morale regeneration
   - Networth calculation

9. **Notification dispatch** — Queued notifications (invasion alerts, spell alerts, op alerts) are sent.

### Why Pre-Calculation?

The pre-calculation design means players always see accurate "next tick" projections on their dashboard. Production numbers, population changes, and queue completions are predictable without guessing. It also means the actual tick application is a fast database write rather than a compute-heavy calculation.

---

## Daily Reset

Once per day (at the hour corresponding to the round's start time), a daily reset runs on top of the normal tick:

**Daily bonus reset:**
- `daily_platinum` flag reset (allows collection of daily platinum bonus)
- `daily_land` flag reset (allows collection of daily land bonus)
- `daily_actions` count reset to the configured maximum

**Inactive dominion management:**
- Dominions that have been inactive beyond a threshold and have not yet completed their protection period are moved to the graveyard (Realm 0). This prevents abandoned new players from occupying realm slots indefinitely.

**Wonder spawning:**
- Wonders spawn on a schedule tied to the round day. Every few days, new wonders appear on the map for realms to contest. Wonder power at spawn scales with the round day — later-spawning wonders are harder to destroy.

**Daily rankings update:**
- All dominion statistics are recalculated and ranked. Rank changes (up/down since previous day) are recorded. Valor scores for top performers are updated.

---

## Protection Period

Every dominion begins the round under protection. During protection:

- The dominion **cannot be attacked** by other players.
- The dominion **cannot take offensive actions** (no invasions, no spy ops, no hostile spells, no exploration).
- The dominion can freely construct buildings, train units, cast self-spells, and manage their economy.

Protection expires after a configured number of ticks. Once protection ends, the dominion enters the competitive arena fully.

**Quick Start option** — Some round configurations offer an accelerated protection mode that provides a starting boost (pre-built infrastructure or resources) in exchange for a shorter protection window. Players who are confident in their early-game skills can opt for this to enter competitive play sooner.

The protection mechanic exists to give new players (and slower starters) a window to learn the economic fundamentals before facing military pressure. It also prevents experienced players from immediately destroying newly-joined competitors.

---

## Tick Numbering

Ticks are numbered from the round's start:

```
Tick = (24 × (day_in_round − 1)) + (hour_in_day − 1)

Day 1, Hour 1 → Tick 0
Day 1, Hour 24 → Tick 23
Day 2, Hour 1 → Tick 24
```

This numbering system is used internally for scheduling, queue calculations, and event logging. From a player perspective, time is displayed as "Day X, Hour Y" rather than raw tick numbers.

---

## Round Leagues

A round league defines the rule variant for that round. The league is set when the round is created and cannot be changed mid-round. Currently the primary active league is **Standard**, but the system is designed to support multiple distinct league types with different mechanical configurations. League differences can affect:

- Realm size targets
- Protection duration
- Tech tree version (v1 classic vs. v2 current)
- Mixed vs. alignment-locked realms
- Any other configurable round parameter

League variety is the mechanism for running experimental or themed rounds distinct from the standard competitive experience.

---

## NPC Dominions

When a round starts, the game spawns AI-controlled (bot) dominions to supplement the player count. Bots are spawned at approximately 80% of the human player count and distributed across realms. They begin with a range of starting land sizes, providing varied targets at different sizes for early-round military players.

Bot dominions are governed by automated strategies and serve several design purposes:
- Provide invasion targets in the early round before players are fully built up.
- Fill out realms that have fewer human players than the target size.
- Provide a baseline competitive pressure that scales with the player pool.

Bots follow simplified decision rules compared to human players but participate in the same tick-driven economy.

---

## Scoring and Rankings

### Networth

The primary competitive metric. Calculated each tick from:
- Land (per acre value)
- Buildings (per building value)
- Specialist units (fixed value each)
- Elite units (value based on max power stat)

Networth is used for invasion range matching: a dominion can only invade targets within a range relative to their own networth/land. This prevents the largest dominions from trivially attacking the smallest.

### Daily Rankings

Each day, all dominions are ranked across dozens of statistics:
- Land, networth
- Total land explored, conquered
- Military, spy, and wizard strength
- All resource production categories
- Building counts

Rankings determine valor awards for top performers and provide the competitive leaderboard that drives long-term goals.

### Valor

A realm-level score accumulated through:
- Successful wonder captures and participation
- Defense contributions
- Top daily ranking positions

Valor provides realm-wide prestige and contributes to end-of-round standings. It is a team score — individual dominion performance contributes to the collective realm valor.

---

## Round Pacing

A typical round unfolds in recognizable phases, even without explicit round-phase rules:

**Early round (Days 1–7):** Protection periods, economic foundation-building, pack coordination, initial exploration. Most dominions are in protection; competitive play has not yet begun in earnest. Tech investment begins, hero leveling starts, first realm government roles fill.

**Mid round (Days 8–21):** Protection ends for most players. Invasions begin. Wonder assaults start. War declarations emerge. Prestige competition heats up. Tech paths diverge meaningfully. The military arms race is in full swing.

**Late round (Days 22+):** The competitive order has largely established itself. Dominant realms hold wonders and maintain offensive pressure. Trailing realms may shift to defensive or economic strategies. Advanced techs become unlockable for leading players. Offensive actions prohibition activates as the end date approaches.

**End of round:** Final rankings lock. Players reflect on the round's outcome and prepare for the next registration cycle.

---

## Interactions With Other Systems

- **[Races & Units](01-races-and-units.md)** — Race is selected at round registration and cannot be changed. All unit, perk, and bonus systems are defined per-round from round start.
- **[Land & Construction](02-land-and-construction.md)** — Construction queue times (measured in ticks) determine how long building takes. Construction costs scale with land reached, which accumulates throughout the round.
- **[Population & Resources](03-population-and-resources.md)** — All resource production and population growth occurs once per tick. Compound growth over many ticks is the core economic engine.
- **[Military](04-military.md)** — Unit training queues and return times are measured in ticks. Morale recovery is tick-based. The offensive actions prohibition at round end prevents final-hour suicides.
- **[Magic](05-magic.md)** — Spell durations count down by tick. Wizard and spy strength recovery is per-tick. Mana decay occurs each tick.
- **[Heroes](07-heroes.md)** — Hero XP accumulates per qualifying action throughout the round. Shrine bonus compounds across the full round length — early shrine investment pays off over more ticks.
- **[Technology](08-technology.md)** — Research points accumulate per tick from Schools. Tech unlocks are permanent for the round. Earlier unlocks provide more total rounds of benefit.
- **[Wonders](09-wonders.md)** — Wonder spawn power scales with round day. Early-round wonders are weaker and easier to capture. Wonder bonuses apply for as long as the realm holds the wonder through the round.
- **[Realms & Diplomacy](10-realms-and-diplomacy.md)** — Realm assignment runs at T-96 hours. Pack registration closes at assignment. War duration auto-expiry is tick-based.

---

## Player Decision Space

**Registration timing** — Registering early allows time to find a pack and participate in the social/compatibility scoring. Last-minute registration means less control over realm placement.

**Protection strategy** — Using protection efficiently (building the economic foundation, training the first wave of units, casting self-buffs) determines how strong the dominion is the moment it enters competitive play. A player who idles through protection starts behind permanently.

**Activity cadence** — OpenDominion does not require continuous play, but players who log in more frequently can respond to attacks faster, recast expiring spells, retrain after losses, and take advantage of windows when enemies are vulnerable. The tick structure rewards consistent check-ins over marathon sessions.

**Round phase awareness** — Actions that are optimal in the early round (heavy economic investment, exploration-focused growth) may be suboptimal in the late round (when military return on land is higher). Recognizing phase transitions and adjusting strategy accordingly is one of the deepest skills in the game.

**End-of-round positioning** — The offensive prohibition window near the end date creates a final-positioning phase where military aggression is impossible. Players who enter this window with the strongest land and networth hold their ranking; last-minute attacks cannot change the outcome. Planning the timing of major offensive campaigns relative to the round end date is a meaningful strategic consideration.

> **Note:** The tick-based economy means that time itself is a resource. A construction project started on Day 3 versus Day 5 produces two additional days of building output. A technology unlocked on Day 8 provides benefits across more of the round than one unlocked on Day 15. Every hour of the round has compounding value — which is why protection period efficiency and early tech/economic decisions have outsized impact on final standings.
