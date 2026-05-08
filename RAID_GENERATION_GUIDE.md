# OpenDominion Raid Generation Guide

This document is a comprehensive reference for generating new raids for OpenDominion. It covers narrative conventions, mechanical constraints, data structures, and worked examples drawn from all existing raids.

---

## Table of Contents

1. Overview of the Raids System
2. Narrative Conventions
3. Raid Data Structure
4. Tactic Types
5. Bonus System
6. Tactic Naming Conventions
7. Objective Design Patterns
8. Full Raid Examples
9. Timing Guidelines
10. Balanced Objective Design Checklist
11. Generating a New Raid
12. Races Quick Reference
13. Hero Classes Quick Reference
14. The World — Land Types and Geography
15. Wonders — The World's Great Structures
16. Magic System — Spells and Arcane Vocabulary
17. Buildings — Infrastructure of the Realm
18. The Nox — Non-Playable Lore Faction
19. Racial Spells — Unique Abilities of Each Race
20. Potential Future Story Threads
21. Canonical Raid Descriptions — Style Reference

---

## 1. Overview of the Raids System

A **Raid** is a multi-objective time-limited event tied to a game Round. Each Raid has 1–3 sequential Objectives, each lasting roughly 24 hours. Players within a realm work together to earn Score toward each Objective's `score_required` threshold. Realms compete independently — each realm races to be the first to complete every Objective.

**Hierarchy:**
```
Round
└── Raid (campaign with narrative arc)
    └── RaidObjective (one sequential "stage", ~24h each)
        └── RaidObjectiveTactic (player actions that earn score)
```

---

## 2. Narrative Conventions

### 2.1 Tone and Setting

OpenDominion is a dark fantasy kingdom management game. The world is ruled by an Emperor commanding allied Realms (groups of 10–15 Dominions / players). The setting has:

- Classic fantasy races (Humans, Elves, Dwarves, Goblins, Orcs, Trolls, Undead, etc.)
- A high magic world with wizards, mages, and arcane technology
- An ancient history of long-dead empires, sealed tombs, and forbidden knowledge
- Cosmic threats alongside mundane political intrigue
- **Planewalkers** — ancient interdimensional conquerors, defeated long ago but not gone
- **The Nox** — a cult of void-worshipping undead, servants of the Lich King
- **Planar Gates** — excavated in Round 70 to redirect a comet; now a source of dimensional instability

### 2.2 Raid Description Style

Raid descriptions are approximately 2–4 paragraphs. They should:

- Establish **the reason for the raid** (threat, opportunity, or both)
- Describe the **location and its dangers** with vivid, evocative detail
- Hint at **what rewards await** (plunder, political gain, forbidden knowledge)
- Escalate stakes if this is a **sequel or continuation** of a prior raid's story

Raid objective names and descriptions are short and punchy — one sentence each, written as a stage title and brief mission briefing.

### 2.3 Story Arcs

Raids benefit from **continuity across a round**. A round typically contains 2 raids that form a two-part story arc:

| Round | Part 1 | Part 2 |
|---|---|---|
| 68 | Lair of the Dragon | Ironhold Citadel |
| 69 | The Island Fortress | The Tomb of Kings |
| 70 | The Nightbringer's Rise | The Lich King's Fury |
| 72 | Planewalker Incursion | Rise of the Hydromancer |

Round 71 had a single standalone raid ("The Gods' Ultimatum") with unusual mechanics (peasant sacrifice).

A **standalone raid** within a round (often the first, shorter one) can introduce the round's arc or be thematically distinct.

### 2.4 Existing World Lore

- The Emperor is a recurring authority figure who commands the Realms
- The Planewalkers are an existential threat from another dimension; they stepped through reactivated Planar Gates in Round 72
- The Lich King was defeated in Round 70 (The Lich King's Fury)
- The Nightbringer is his avatar/manifestation
- The Planar Gates were excavated to redirect a comet, then stabilized but remain dangerous
- "The Void" is the space between planes — the Lich King's domain

---

## 3. Raid Data Structure

### 3.1 Raid Fields

```php
Raid::create([
    'round_id' => $round->id,
    'name' => 'Raid Name',                            // Short title (2-4 words)
    'description' => 'HTML-formatted lore text...',   // 2-4 paragraphs, may use <br/> and <em>
    'reward_resource' => 'resource_platinum',          // Per-objective-completion reward
    'reward_amount' => 7500000,
    'completion_reward_resource' => 'prestige',        // For completing the full raid
    'completion_reward_amount' => 75,
    'start_date' => now()->addDays(N),
    'end_date' => now()->addDays(N),
]);
```

**Valid reward resources:**
- `resource_platinum` — most common raid reward
- `resource_gems` — used for higher-prestige or treasure-themed raids
- `resource_tech` — used for knowledge/research-themed raids (rare)
- `prestige` — always used as the `completion_reward_resource`

**Reward scale guidelines (based on ~10 active players per realm):**

| Raid Size | `reward_amount` (platinum) | `completion_reward_amount` (prestige) |
|---|---|---|
| Small / intro (1 obj) | 1,500,000 – 5,000,000 | 25 |
| Standard (3 obj, 3 days) | 5,000,000 – 10,000,000 | 60 – 75 |
| Large / climactic (3 obj, 3 days) | 10,000,000 – 15,000,000 | 90 – 120 |

Use `resource_gems` reward instead of platinum to signal "this is a treasure/loot raid."

---

### 3.2 RaidObjective Fields

```php
RaidObjective::create([
    'raid_id' => $raid->id,
    'name' => 'Stage Name',              // 2-4 word label
    'description' => 'One sentence.',    // Brief mission briefing
    'order' => 1,                        // Sequential position (1, 2, 3)
    'score_required' => 100000,          // Realm score threshold to complete
    'start_date' => now()->addDays(N),
    'end_date' => now()->addDays(N+1),   // Each objective spans ~24 hours
]);
```

**Score required scale (per realm, ~10 active players):**

| Raid Tier | Obj 1 | Obj 2 | Obj 3 |
|---|---|---|---|
| Small intro | 50,000 | — | — |
| Standard | 75,000 – 100,000 | 100,000 – 125,000 | 125,000 – 150,000 |
| Climactic | 300,000 – 350,000 | 325,000 – 500,000 | 400,000 – 600,000 |

The final objective of a climactic raid often has the highest score requirement (500,000–600,000) to represent the hardest challenge.

---

### 3.3 RaidObjectiveTactic Fields

```php
RaidObjectiveTactic::create([
    'raid_objective_id' => $objective->id,
    'type' => 'espionage',               // See tactic types below
    'name' => 'Tactic Name',             // Evocative action phrase
    'attributes' => [...],               // Type-specific JSON
    'bonuses' => [...],                  // Optional bonus multipliers (or null)
]);
```

---

## 4. Tactic Types

Each tactic type has a specific cost structure and point scale. Every objective should have **2–5 tactics** covering 2–3 different types to give all player archetypes a way to contribute.

### 4.1 Espionage

Used for scouting, infiltration, sabotage, intelligence-gathering phases.

```json
{
  "strength_cost": 15,
  "morale_cost": 0,
  "points_awarded": 2
}
```

```json
{
  "strength_cost": 30,
  "morale_cost": 10,
  "points_awarded": 4
}
```

- `strength_cost`: spy strength spent (15 = small, 30 = large)
- `morale_cost`: morale cost (0 = passive intel, 10 = active disruption)
- `points_awarded`: score earned per use (2–6 for typical objectives; 5–30 for early-game smaller objectives)
- Always provide **two espionage tiers** (cheap/weak and expensive/strong)
- Thematically appropriate for: scouting, infiltration, code-breaking, sabotage, stealth operations

**Bonus example:** `{"hero_class":{"infiltrator":1.1}}`

---

### 4.2 Exploration

Used for mapping, searching, scouting wide areas. High cost, high reward per action.

```json
{
  "draftee_cost": 1000,
  "morale_cost": 10,
  "points_awarded": 6000
}
```

```json
{
  "draftee_cost": 1500,
  "morale_cost": 30,
  "points_awarded": 20000
}
```

- `draftee_cost`: military draftees consumed (800–2000 range)
- `morale_cost`: morale penalty (10 = routine, 25–30 = dangerous)
- `points_awarded`: score per use (6,000–20,000 depending on cost)
- Always provide **two exploration tiers**
- Thematically appropriate for: charting territory, searching ruins, locating hidden things, mapping catacombs

---

### 4.3 Magic

Used for enchanting, warding, dispelling, summoning, channeling.

```json
{
  "strength_cost": 15,
  "mana_cost": "1",
  "points_awarded": 2
}
```

```json
{
  "strength_cost": 25,
  "mana_cost": "2.5",
  "points_awarded": 4
}
```

- `strength_cost`: wizard strength spent (15 = minor, 25 = significant)
- `mana_cost`: multiplied by the dominion's land size (stored as string); `"1"` = 1× land, `"2.5"` = 2.5× land, `"5"` = 5× land
- `points_awarded`: score per use (2–15 typical; scale proportionally to mana cost)
- Always provide **two magic tiers**
- Thematically appropriate for: dispelling, warding, summoning, channeling, rituals, enchanting
- Bonus example: `{"hero_class":{"sorcerer":1.1}}` or `{"daily_ranking":{"masters-of-water":1.2}}`

---

### 4.4 Investment

Used for contributing material resources. Repeatable up to a `limit` times per dominion.

```json
{
  "resource": "resource_lumber",
  "amount": 20000,
  "points_awarded": 5000,
  "limit": 10
}
```

**Valid resource keys:**
- `resource_platinum` — most liquid; lower points-per-unit value
- `resource_lumber` — construction focus
- `resource_ore` — military/industrial focus
- `resource_gems` — premium resource, high points per unit
- `resource_mana` — arcane/magical focus
- `resource_boats` — naval operations (amount: 20, points: 2500)
- `resource_tech` — research/knowledge focus (amount: 500, points: 5000)
- `peasants` — rare/special; sacrifice mechanics (amount: 1000, points: 1000)

**Typical amounts and point values:**

| Resource | Amount | Points |
|---|---|---|
| resource_platinum | 20,000 | 1,000 |
| resource_lumber | 20,000 | 2,500–5,000 |
| resource_ore | 20,000 | 2,500–5,000 |
| resource_gems | 15,000 | 5,000 |
| resource_mana | 10,000–20,000 | 5,000 |
| resource_boats | 20 | 2,500 |
| resource_tech | 500 | 5,000 |
| peasants | 1,000 | 1,000 |

- `limit` is **always required** for investment tactics (typically 10)
- Group investment tactics thematically (e.g., "Forge Siege Weapons (Lumber)" + "Forge Siege Weapons (Ore)")
- Thematically appropriate for: building equipment, funding expeditions, supplying armies, powering arcane devices

**Bonus example:** `{"hero_class":{"blacksmith":1.1}}` or `{"tech":{"tech_13_1":1.1}}`

---

### 4.5 Invasion

Military assault tactics. Points are **dynamically calculated based on damage dealt**, not a fixed value.

```json
{
  "casualties": 3.5
}
```

- `casualties`: percentage of offensive force that dies (3.0 = 3%, 3.5 = 3.5%)
- Points are calculated by the engine based on offensive power sent — do not set `points_awarded`
- Name the tactic after the **target location** (e.g., "Drake Nests", "Outer Gates of Ironhold", "Void Portals")
- Each objective usually has **one invasion tactic** at most
- Thematically appropriate for: direct assaults, clearing enemies, breaking fortifications

---

### 4.6 Hero

Hero battle tactics. The hero fights NPC enemies. One-time completion per dominion (very high point value).

**Two formats:**

**Format A: Pre-built NPC stats (deprecated, avoid for new raids)**
```json
{
  "name": "Gate Warden",
  "health": 150,
  "attack": 40,
  "defense": 25,
  "evasion": 10,
  "focus": 10,
  "counter": 50,
  "recover": 20,
  "strategy": "counter",
  "points_awarded": 10000
}
```

**Format B: Named encounter (preferred)**
```json
{
  "name": "Encounter Display Name",
  "encounter": "encounter_key",
  "points_awarded": 10000
}
```

**Available encounter keys and their descriptions:**

| Key | Enemies | Difficulty | Source Raid |
|---|---|---|---|
| `dragonkin` | 3× Dragonkin (60hp, attack 40, balanced) | Medium | Lair of the Dragon |
| `gate_warden` | 1× Gate Warden (150hp, counter 50, counter strategy) | Hard | Ironhold Citadel |
| `rebel_corsair` | 3× Rebel Corsair (60hp, blade_flurry) | Medium | The Island Fortress |
| `rebel_admiral` | 1× Rebel Admiral (150hp, blade_flurry + enrage) | Hard | The Island Fortress |
| `fallen_kings` | 3× Fallen Kings (undying, mixed strategies) | Very Hard | The Tomb of Kings |
| `eternal_guardian` | 1× Eternal Guardian (undying_legion + summon_skeleton) | Very Hard | The Tomb of Kings |
| `nightbringer` | 1× Nightbringer (200hp, elusive + darkness) + 2× Nox Cultist | Very Hard | The Nightbringer's Rise |
| `lich_king` | 1× Lich King (150hp, enrage) + 1× Tome of Power (multi-phase) | Extremely Hard | The Lich King's Fury |
| `planewalker_golems` | 3× Void Golem (50hp, fortify + hardiness) | Hard | Planewalker Incursion |
| `planewalker` | 1× The Planewalker (200hp, elusive + summon_golem + wounded_retreat) | Boss | Rise of the Hydromancer |

**Hero tactic point values:**

| Difficulty | points_awarded |
|---|---|
| Medium (3 enemies) | 2,500–5,000 |
| Hard (1 tough enemy) | 7,500–10,000 |
| Very Hard | 10,000–15,000 |
| Boss / climactic | 75,000 |

- Objectives typically have **one hero tactic** (sometimes two if offering multiple encounters)
- Name the tactic as a challenge verb: "Duel the ...", "Challenge the ...", "Face the ...", "Defeat the ...", "Confront the ..."

---

## 5. Bonus System

Bonuses are optional JSON objects on tactics. They multiply the `points_awarded` for players who qualify.

### 5.1 Bonus Schema

```json
{
  "race_bonuses": {"halfling": 1.2, "elf": 1.1},
  "tech_bonuses": {"spy_networks": 1.15},
  "hero_class": {"infiltrator": 1.1, "sorcerer": 1.1},
  "tech": {"tech_13_1": 1.1},
  "daily_ranking": {"masters-of-water": 1.2}
}
```

Note: Both `race_bonuses`/`tech_bonuses` and `race`/`tech` formats appear in the codebase — use the flat `race`/`tech`/`hero_class` keys for newer raids.

### 5.2 Race Bonus Thematic Guide

Apply race bonuses to tactics that thematically align with the race's strengths:

| Race | Good for |
|---|---|
| `halfling` | Espionage tactics |
| `elf` / `wood-elf-rework` | Espionage + magic tactics |
| `dark-elf-rework` | Magic + espionage tactics |
| `lizardfolk` | Espionage tactics, water-themed |
| `dwarf` | Investment (ore/lumber), exploration (mountains) |
| `gnome` | Investment (tech/ore), exploration |
| `human` | Military/invasion, balanced |
| `orc` | Invasion tactics |
| `troll` | Invasion tactics |
| `merfolk` | Water-themed magic and exploration |
| `sylvan` | Forest/nature exploration, magic |
| `undead-rework` | Death/void themed magic |
| `vampire` | Dark magic tactics |
| `spirit-rework` | Spirit/void magic |
| `demon` | Combat-heavy invasion |

### 5.3 Hero Class Bonus Guide

| Class | Good for |
|---|---|
| `alchemist` | Platinum investment tactics |
| `architect` | Construction/lumber investment |
| `blacksmith` | Ore/weapon investment |
| `engineer` | Siege weapon investment |
| `farmer` | Food-related investment |
| `healer` | Healing/rescue magic |
| `infiltrator` | Espionage tactics |
| `sorcerer` | All magic tactics |
| `scholar` | Research/tech investment |

---

## 6. Tactic Naming Conventions

### Espionage Tactic Names (verbs + object):
- `"Interrogate Captives"`, `"Infiltrate the [Location]"`, `"Sabotage the [Target]"`, `"Steal [Artifact]"`, `"Decode [Target]"`, `"Eliminate Lookouts"`, `"Scout the [Area]"`, `"Access [Records/Data]"`, `"Acquire [Schematics]"`

### Exploration Tactic Names (action + location):
- `"Scout the [Descriptive Area]"`, `"Search the [Specific Zone]"`, `"Map the [Geography]"`, `"Chart [Location]"`, `"Locate [Thing]"`, `"Search for [Target]"`

### Magic Tactic Names (evocative spell names):
- Tier 1 (weak/cheap): `"Dispel the [Enchantments]"`, `"Enchant [Object]"`, `"Channel [Effect]"`, `"Ward [Target]"`, `"Activate [Device]"`
- Tier 2 (strong/expensive): `"[Impressive Spell Name]"`, `"Cast [Epic Spell]"`, `"Conjure [Powerful Effect]"`, `"Summon [Forces]"`, `"Invoke [Ancient Power]"`

### Investment Tactic Names (describe the contribution):
- `"Forge [Equipment]"`, `"Fund [Activity]"`, `"Establish [Infrastructure]"`, `"Deploy [Resource Type]"`, `"Craft [Item]"`, `"Power [Device]"`
- Often append the resource type when offering multiples: `"Forge Siege Weapons (Lumber)"` vs `"Forge Siege Weapons (Ore)"`

### Invasion Tactic Names (the target location):
- Named after the physical location being stormed, not the action: `"Drake Nests"`, `"Outer Gates of Ironhold"`, `"Void Portals"`, `"The Coldlight Beacons"`, `"Island Fortress"`

---

## 7. Objective Design Patterns

Each objective should follow one of these archetypal patterns based on its narrative role:

### Pattern A: "Reconnaissance" (early objectives)
- 2 espionage tactics + 2 exploration tactics
- Score: 50,000–100,000
- Theme: Gathering information, scouting, learning the lay of the land
- Example: *Charting the Waters*, *Infiltrate the Cult*, *The Hunt Begins*

### Pattern B: "Preparation / Logistics" (middle objectives)
- 2 exploration tactics + 2 investment tactics + optional magic
- Score: 75,000–150,000
- Theme: Assembling forces, building equipment, establishing a foothold
- Example: *March to Ironhold*, *Excavate the Planar Gates*, *Charting the Waters (fleet-building)*

### Pattern C: "Assault / Combat" (final objectives)
- 1–2 magic tactics + 1 invasion tactic + 1 hero battle + optional espionage
- Score: 100,000–600,000 (highest of the raid)
- Theme: The climactic fight
- Example: *Slaying the Beast*, *The Final Justice*, *Defeat the Nightbringer*

### Pattern D: "Restoration / Sealing" (special final objectives)
- 2 investment tactics + 2 magic tactics + optional espionage
- Score: 350,000–500,000
- Theme: Undoing damage, sealing threats, using technology
- Example: *Relocate the Comet*, *The Final Seal*

### Pattern E: "Mixed Arms" (versatile middle/late objectives)
- 2 espionage + 1 magic tier pair + 1 hero + 1 invasion
- Score: 125,000–325,000
- Theme: Full-spectrum combat phase
- Example: *Breaking the Blockade*, *The March of the Dead*

---

## 8. Full Raid Examples

### Example 1: Simple Intro Raid (1 objective)

> **Bandit Encampments** — Espionage + Invasion only, 50,000 score, 1 day

```
Obj 1: "Eliminate the Threat"
  - Espionage: "Locate the Camps" (strength:15, morale:0, pts:10)
  - Espionage: "Ambush Patrols" (strength:30, morale:10, pts:30)
  - Invasion: "Bandit Encampment" (casualties:3.5)
```

### Example 2: Three-Act Dragon Raid (3 objectives, escalating)

> **Lair of the Dragon** — 3 days, 3 objectives, escalating score requirements

```
Obj 1: "The Hunt Begins" (score: 50,000, 24h)
  - Exploration: "Search the Mountains" (draftees:1000, morale:10, pts:1000)
  - Exploration: "Scale the Northern Peaks" (draftees:1500, morale:25, pts:3000)
  - Espionage: "Question the Survivors" (strength:15, pts:5)
  - Espionage: "Track the Beast" (strength:30, pts:15)

Obj 2: "Clear the Drake Nests" (score: 75,000, 24h)
  - Hero: "Slay the Sentinels" (encounter: dragonkin, pts:5000)
  - Invasion: "Drake Nests" (casualties:3.5)

Obj 3: "Slaying the Beast" (score: 100,000, 24h)
  - Magic: "Enchant Weaponry" (strength:15, mana:1, pts:5)
  - Magic: "Dragon-bane Sorcery" (strength:25, mana:3, pts:15)
  - Invasion: "Lair of the Dragon" (casualties:3.5)
```

### Example 3: Climactic Narrative Raid (3 objectives, large scale)

> **Rise of the Hydromancer** — 350k/500k/600k score requirements, planewalker boss

```
Obj 1: "The Golem Tide" (score: 350,000)
  - Magic: "Summon Tidal Waves" (strength:15, mana:1, pts:2) [bonus: daily_ranking masters-of-water 1.2]
  - Magic: "Conjure Water Elementals" (strength:25, mana:2.5, pts:4) [bonus: hero_class sorcerer 1.1]
  - Invasion: "The Planar Gates" (casualties:3)

Obj 2: "Secrets of the Gates" (score: 500,000)
  - Investment: "Applied Research" (resource:resource_tech, amount:500, pts:5000, limit:10)
  - Investment: "Trace Dimensional Echoes" (resource:resource_mana, amount:20000, pts:5000, limit:10)
  - Espionage: "Steal Planewalker Artifacts" (strength:15, pts:2) [bonus: hero_class infiltrator 1.1]
  - Espionage: "Decode Gate Inscriptions" (strength:30, pts:4) [bonus: tech tech_9_17 1.1]

Obj 3: "Coordinated Strike" (score: 600,000)
  - Hero: "Confront the Void Traveler" (encounter: planewalker, pts:75000)
```

---

## 9. Timing Guidelines

Each raid spans 3 days (3 objectives × 24 hours each), with dates set to midnight UTC:

```
start_date = ROUND_START + N days  (raid begins)
Obj 1: start = raid.start,         end = start + 1 day
Obj 2: start = start + 1 day,      end = start + 2 days
Obj 3: start = start + 2 days,     end = start + 3 days = raid.end_date
```

For a 1-objective intro raid, the full raid spans 1 day.

Multiple raids within a round can overlap or be sequential — existing raids have run back-to-back with some sharing a round.

---

## 10. Balanced Objective Design Checklist

When designing an objective's tactics, verify:

- [ ] **All player types can contribute** — spies, wizards, military players, and resource players each have at least one relevant tactic
- [ ] **Tiers exist within types** — most tactic types appear in pairs (cheap/weak and expensive/strong)
- [ ] **Points scale with cost** — more expensive tactics give proportionally more points
- [ ] **Hero battles are one-time** — only one hero tactic per objective (since each dominion can only do it once, it must be worth a lot relative to repeatable tactics)
- [ ] **Invasion is thematic** — the invasion target name reflects what's being attacked
- [ ] **Investment tactics have `limit`** — always set to 10 for investment type
- [ ] **Names are evocative** — tactic names should make thematic sense as actions within the narrative
- [ ] **Bonuses are thematically appropriate** — don't give dwarf bonuses to water tactics

---

## 11. Generating a New Raid

When generating a new raid, follow this process:

### Step 1: Establish the Narrative Hook
- What is the threat or opportunity?
- Where does it take place?
- Does this connect to the existing story arc (Planewalkers, void, imperial politics)?

### Step 2: Design the Three Acts
1. **Act 1 — Discovery/Approach**: Gathering intel, surveying the site, preparing
2. **Act 2 — Engagement**: First contact with the main threat, establishing position
3. **Act 3 — Climax**: Final confrontation, boss battle, sealing/resolution

### Step 3: Select Tactic Mix per Objective
Use the patterns from Section 7 to choose a pattern for each objective, then pick tactics appropriate to the narrative.

### Step 4: Set Scaling
- Are there 10 active players per realm? Use standard scale.
- Is this a climactic end-of-round raid? Use 300k–600k score requirements.
- Is this an intro/standalone raid? Use 50k–100k.

### Step 5: Add Hero Encounters
- Pick the appropriate encounter key from Section 4.6
- Place hero battles in Act 2 (medium difficulty) or Act 3 (hard/boss difficulty)
- The final objective of a climactic raid should use the hardest available encounter

### Step 6: Add Flavor via Bonuses
- Add 1–2 bonus entries to 1–3 tactics per objective
- Align bonuses thematically (naval raid → merfolk/lizardfolk; magical raid → sorcerer class)

---

## 12. Races Quick Reference

The following races exist in the game. Use race bonuses to reward races whose themes align with the raid's narrative.

| Race | Alignment | Home | Specialty |
|---|---|---|---|
| Dark Elf | Evil | Cavern | Magic + espionage hybrid |
| Demon | Evil | Cavern | Raw offense, mana units |
| Dwarf | Good | Mountain | Ore, construction, defense |
| Firewalker | Good | Cavern | Platinum, construction |
| Gnome | Good | Mountain | Ore, technology, mechanical |
| Goblin | Evil | Hill | Population, gems, plunder |
| Halfling | Good | Hill | Spy power, defense |
| Human | Good | Plain | Balanced, versatile |
| Icekin | Evil | Mountain | Defense, mana-free food |
| Kobold | Good | Hill | Mass population, boats |
| Lizardfolk | Evil | Water | Spy power, water combat |
| Lycanthrope | Evil | Cavern | Conversion, defense |
| Merfolk | Good | Water | Naval, krakens |
| Nomad | Evil | Plain | Fast return, mobile warfare |
| Orc | Evil | Forest | Lumber, prestige-based offense |
| Spirit | Good | Water | Immortal wizards, no food |
| Sylvan | Good | Forest | Lumber, food, forest magic |
| Troll | Evil | Plain | Brute offense, ore-heavy |
| Undead | Evil | Swamp | Conversion, mana, no food |
| Vampire | Evil | Swamp | Conversion, immortal, mana |
| Wood Elf | Good | Forest | Spy + wizard versatility |

---

## 13. Hero Classes Quick Reference

| Class | Key | Passive Perk | Combat Special |
|---|---|---|---|
| Alchemist | `alchemist` | Platinum production | Volatile Mixture (150% damage, 20% self-hit) |
| Architect | `architect` | Construction cost | Fortify (absorb 20 damage) |
| Blacksmith | `blacksmith` | Military cost | Forge (increase attack permanently) |
| Engineer | `engineer` | Castle investment | Demolish (AoE, bypasses defenses) |
| Farmer | `farmer` | Food production | Hardiness (survive at 1hp once) |
| Healer | `healer` | Casualties reduction | Mending (enhanced recovery) |
| Infiltrator | `infiltrator` | Spy power | Shadow Strike (unevadable, +2 vs defenders) |
| Sorcerer | `sorcerer` | Wizard power | Great Flood (AoE, bypasses defenses) |
| Scholar | `scholar` | Max population | Combat Analysis (reduce enemy defense) |
| Scion | `scion` | Explore cost | Last Stand (stats +10% when low) |

---

## 14. The World — Land Types and Geography

Every Dominion owns land divided into seven types. Land type determines which buildings a dominion can construct on that terrain, and each race has a "home land type" where their racial home (`home`) building is built. Understanding the geography matters for raid theming: a raid set in a swamp will feel different from one in caverns or on the open ocean.

| Land Type | Feel / Flavor | Buildings Found There | Races at Home |
|---|---|---|---|
| **Plain** | Open farmland, cities, roads | Alchemy, Farm, Smithy, Masonry | Human, Nomad, Troll |
| **Mountain** | Peaks, mines, fortresses | Ore Mine, Gryphon Nest | Dwarf, Gnome, Icekin |
| **Swamp** | Bogs, marshes, ruins | Tower, Wizard Guild, Temple | Undead, Vampire, Nox, Demon |
| **Cavern** | Underground networks, tunnels | Diamond Mine, School | Dark Elf, Firewalker, Lycanthrope |
| **Forest** | Ancient woods, groves | Lumberyard | Orc, Sylvan, Wood Elf |
| **Hill** | Rolling terrain, strongholds | Factory, Guard Tower, Shrine, Barracks | Goblin, Halfling, Kobold |
| **Water** | Coasts, reefs, open sea | Dock | Lizardfolk, Merfolk, Spirit |

**Using Geography in Raids:**
- A raid set in a **swamp** naturally involves undead, Nox, vampires, and dark magic — towers and wizard guilds are the infrastructure at risk
- A raid in **caverns** suggests underground lore, dark elves, ancient buried things, firewalkers
- A **mountain** raid means dwarves, gnomes, sieges, ore-rich fortresses — gryphon nests and mines
- A **forest** raid means ancient trees, sylvan spirits, wood elves, orcs — lumberyards and primal magic
- An **ocean/water** raid uses naval forces, merfolk, krakens, coastal raids — docks and boats are the resource at stake
- A **hill** raid has factories, barracks, shrines — industrial or religious themes

---

## 15. Wonders — The World's Great Structures

Wonders are powerful realm-level structures that grant bonuses to the controlling realm. Realms compete to attack and capture Wonders throughout a round. They are **central to the world's political landscape** and make excellent raid inspiration: protecting a Wonder, recapturing a stolen one, destroying a corrupted one, or excavating a lost ancient one are all valid raid hooks.

### Active Wonders (currently in the game)

| Name | Effect | Lore Theme |
|---|---|---|
| **Wayfarer's Outpost** | +2% platinum, -5% explore cost | Ancient travelers' network, road system |
| **Planar Gates** | Grants a free tech while controlled | Interdimensional portals; central to Round 70–72 story |
| **Gnomish Mining Machine** | +20% ore production | Gnome mechanical marvel, deep-mine excavator |
| **Guild of Shadows** | +25% spy power, -15% spy losses | Legendary thieves' guild; underground spy network |
| **Hanging Gardens** | +25% food production | Ancient wonder of agricultural bounty |
| **Ancient Library** | +10% castle investment bonus | Repository of lost knowledge |
| **Halls of Knowledge** | +10% research, +15 raw research | Scholar citadel; seat of learning |
| **Great Oracle** | -15% spell cost, +30% wizard power | Temple of prophecy and arcane mastery |
| **Fountain of Youth** | +3% max population | Legendary spring of immortality |
| **City of Gold** | +4% platinum production | A metropolis built on trade wealth |
| **Ruby Monolith** | -10% offense/defense casualties | Ancient monument of protection |
| **High Cleric's Tower** | Kills immortal units, forces max casualties | Holy tower that destroys even undying things |
| **Ivory Tower** | 50% chance to block enemy spells | Magical defense citadel |
| **Underground Society** | 50% chance to block enemy spy ops | Secret network of counter-intelligence |
| **Factory of Legends** | -20% construction cost | The greatest forge in the known world |
| **Imperial Armada** | Boats cannot be sabotaged, -1% guard tax | The Emperor's naval fleet |
| **Wizard Academy** | -50% enemy spell damage | Premier arcane institution |
| **Golden Throne** | +25% prestige gains | The seat of power; whoever holds it claims legitimacy |
| **School of War** | +4 barracks housing | Military academy that trains soldiers efficiently |
| **Horn of Plenty** | +2% to ALL resources | Mythic artifact of abundance |
| **Astral Panopticon** | Grants Surreal Perception | A watchtower that sees into the arcane beyond |
| **Great Market** | +10% employment, +20% exchange rates | The realm's financial and trade center |

### Inactive Wonders (lore exists; currently not in rotation)

These wonders are defined in the game data but not active — they exist in the lore as historical structures that may have been destroyed, lost, or sealed away. **Excellent targets for raid narratives.**

| Name | Effect | Story Potential |
|---|---|---|
| **Lair of the Dragon** | +10% offense, +5% defense, -20% food | The dragon's den — tied to Round 68's *Lair of the Dragon* raid |
| **Portals of Transportation** | Units return 3 hours faster | Ancient teleportation network (pre-Planar Gates?) |
| **Obelisk of Power** | +5% offense and defense | A monolith of pure magical force; origin unknown |
| **Great Wall** | +10% defense | The legendary defensive fortification |
| **Spire of Illusion** | Clear Sights only 85% accurate | A tower that bends perception itself |
| **Onyx Mausoleum** | +25% offensive casualties you inflict | Black death-monument; amplifies destruction |
| **Monument of Protection** | +5% defense | Ancient protective shrine |
| **Altar of Heroes** | +100% hero bonus, +20% hero XP | Sacred altar for champion worship |
| **Temple of the Damned** | -5% enemy defense, -2.5% your own defense | A cursed structure that bleeds power |
| **Urg, the Devourer** | Living wonder; attacks the 3 least-active realms daily | A sentient ancient monster. Power: 1,000,000 |

### Urg, the Devourer

Urg deserves special mention. He is not a building but a **living entity** — a sentient Wonder of immense power (1,000,000 HP vs ~75,000–150,000 for normal wonders). Every 24 hours he attacks a random dominion in each of the three realms that dealt the least damage to him that day. Urg says: *"Urg smash!"*

He is **not currently active** but represents a unique lore element — a pre-civilizational monster that has survived all wars. A raid to re-imprison, distract, or study Urg would be tonally distinct from all prior raids.

### Wonders as Raid Hooks

- **A lost wonder has been rediscovered**: Rival realms race to claim or destroy it before it falls to enemies
- **A wonder is being corrupted**: An enemy faction is twisting the Great Oracle or the High Cleric's Tower to their purposes
- **A wonder was stolen**: Imperial agents stole the Ancient Library; the Emperor wants it back
- **A wonder is being constructed**: Early phase needs resource contributions (investment tactics), later phases need military protection (invasion tactics)
- **Urg awakens again**: A raid to deal enough damage to drive the Devourer back into slumber

---

## 16. Magic System — Spells and Arcane Vocabulary

Spells in OpenDominion fall into four categories. Understanding them helps write authentic magic-themed raid tactic names and lore.

### Information Spells (cost: 0.5–1 mana × land)
Used for gathering intelligence. Heroes and spies in the game world use these to surveil enemies.
- **Clear Sight** — reveals an enemy dominion's status
- **Vision** — reveals an enemy's spell and unit queue
- **Revelation** — reveals enemy active spells
- **Disclosure** — reveals enemy tech

*Raid tactic flavor: espionage phase magic, "decrypting" enemy movements, scrying*

### Hostile Spells (launched against enemies; duration 8 hours)
The war spells of the world, used to devastate enemy economies:
- **Plague** — -50% population growth
- **Insect Swarm** — -15% food production
- **Great Flood** — -25% boat production (naval disruption)
- **Earthquake** — destroys gem and ore production
- **Disband Spies** — converts enemy spies into draftees
- **Fireball** — burns peasants and food, applies a "Burning" status
- **Lightning Bolt** — destroys castle improvements (science, forges, walls, keep)
- **Cyclone** — damages Wonders directly

*Raid tactic flavor: "Cast Fireball Barrage", "Invoke the Lightning Storm", "Unleash the Great Flood", "Channel Plague Winds"*

### Self-Buffing Spells (enhance own dominion; duration 6–12 hours)
These represent the arcane infrastructure of civilization:
- **Gaia's Watch** — +10% food production
- **Ares' Call** — +10% defensive power
- **Midas Touch** — +10% platinum production
- **Mining Strength** — +10% ore production
- **Harmony** — +50% population growth
- **Fool's Gold** — protects resources from theft (cooldown: 20h)
- **Surreal Perception** — reveals incoming spy operations
- **Energy Mirror** — reduces enemy spell damage by 15%, duration -1
- **Amplify Magic** — doubles self-spell cost but extends duration 50%

*Raid tactic flavor: "Weave Protective Wards", "Cast Midas Blessings", "Maintain Fool's Gold Shields"*

### Racial Spells (unique to specific races)
Each race casts one powerful racial self-buff. These define the flavor of a race's magic. See Section 19 for the full list.

### Wonder Spells
- **Cyclone** — the only direct wonder-attacking spell; used in Wonder wars

### Magic as Raid Narrative
The spell list tells us that the world has **institutionalized magic** — Wizard Guilds train mages, Towers produce mana, and Temples protect populations. An attack on these institutions (burning down wizard guilds, draining tower mana for a ritual) is a legitimate raid motivation. The **Energy Mirror** suggests battles over arcane countermeasures. **Amplify Magic** suggests the concept of over-channeling, which could be narratively disastrous.

---

## 17. Buildings — Infrastructure of the Realm

Buildings are the physical infrastructure of dominions, built on specific land types. They give raids authentic environmental context — what would burn in a dragon attack, what would be targeted by a saboteur, what the army marches past.

### Building Lore and Function

| Building | Land Type | Function | Narrative Significance |
|---|---|---|---|
| **Home** | Racial home land | Houses 30 people | The civilian heart of a dominion |
| **Alchemy** | Plain | 45 platinum/hr | Trade district, mint, or alchemical laboratory |
| **Farm** | Plain | 80 food/hr | Agricultural base; burning farms = starvation |
| **Smithy** | Plain | Reduces military training cost | The forge districts; armorers and weapon-makers |
| **Masonry** | Plain | +2.75% castle bonus, absorbs Lightning Bolt | Stone fortifications; the castle's own walls |
| **Ore Mine** | Mountain | 60 ore/hr | The deep mines of dwarves and gnomes |
| **Gryphon Nest** | Mountain | +1.6% offense per 1% owned | Aerial stables for war-gryphons |
| **Tower** | Swamp | 25 mana/hr | Arcane spires producing magical energy |
| **Wizard Guild** | Swamp | 5 mana/hr + protects peasants from Fireball | Mage academies; wizards trained here |
| **Temple** | Swamp | +6% pop growth, -1.35% enemy defense | Religious centers; high priests and acolytes |
| **Diamond Mine** | Cavern | 15 gems/hr | Underground gem extraction |
| **School** | Cavern | Produces research points | Centers of learning; scholars and inventors |
| **Lumberyard** | Forest | 50 lumber/hr | Timber operations in ancient forests |
| **Factory** | Hill | -5% construction cost and rezone cost | Industrial centers; the gnome ideal |
| **Guard Tower** | Hill | +1.6% defense per 1% owned | Defensive watchtower networks |
| **Shrine** | Hill | +40% hero XP and bonus per 1% owned | Sacred places where heroes are consecrated |
| **Barracks** | Hill | Houses 36 military units | Military garrisons and training grounds |
| **Dock** | Water | 0.05 boats/hr + 40 food/hr, protects boats | Naval infrastructure; shipyards and harbors |

### Buildings in Raid Context

**Sabotage targets**: A raid against an enemy's infrastructure might involve destroying Smithies (crippling retraining), burning Farms (causing starvation), collapsing Ore Mines, or flooding Docks.

**Defensive structures**: A raid to build or protect could involve constructing Guard Towers to hold a pass, establishing Barracks in newly captured territory, or fortifying a Masonry-built keep.

**Sacred/important places**: Temples draw acolytes and raise populations — raiding a temple is a desecration. Shrines amplify hero power — a Shrine of the Fallen might contain ancient power worth fighting over. Schools hold research — an enemy burning the Schools of a realm is a grave offense.

---

## 18. The Nox — Non-Playable Lore Faction

The Nox are a **non-playable race** in the game (marked `playable: false`) but are deeply embedded in the game's lore, appearing as the primary antagonist faction in the Round 70 arc.

**Description**: *"Legends say that deep under the Undead swamps there exists a supernatural void; an unholy abyss absent of all light, in which unfathomable creatures skitter about in the inky dark. Among them are the Nox. The Nox wear darkness like a cloak, dragging night into day in vast clouds that defy all reason, enveloping entire empires in an alien twilight wherever they strike. When the daylight returns, there's not a trace of their enemies left."*

**Home**: Swamp (same as Undead and Vampire)

**Units**:
- **Imp** — cheap 3-offense unit, no boat needed, costs 300 platinum
- **Fiend** — 3-defense unit, costs 325 platinum + 8 mana
- **Nightshade** — defensive specialist, gains +defense from swamp land (up to +4), costs 975 platinum + 25 mana
- **Lich** — 5-offense elite, near-immortal (-50% casualties), costs 970 platinum

**Racial Perks**: -20% food consumption, +10% mana production, +5 boat capacity, +10% hero experience

**In the Lore**:
- The Nox serve the **Lich King** and worship the **Nightbringer** as a god
- They perform rituals around **Coldlight Beacons** — strange pillars of cold light that reach into the sky, calling a comet toward the planet
- The **Nox Cult** (Cult of the Nightbringer) is their religious faction
- Their unit the **Lich** mirrors the **Lich King** himself — a powerful undying entity
- The **Nightshade** unit suggests stealth and shadow operations, consistent with their darkness theme
- Nox hero names are guttural, consonant-heavy alien-sounding names (Cruzoc, Tzaddo, Axzees, Zur'ghex, etc.)

**Using the Nox in New Raids**:
- Nox forces are encountered in swamps, underground, and anywhere darkness gathers
- Their methods involve ambush, darkness manipulation, and overwhelming numbers
- A Nox uprising, splinter cult, or remnant faction could justify a new raid even after the Lich King's defeat
- The **Nightshade** unit type suggests invisible threats and stealth — espionage-heavy encounters
- Nox **Liches** are hard to kill (-50% casualties) — a Lich miniboss in a hero battle would be a tough but thematic encounter

---

## 19. Racial Spells — Unique Abilities of Each Race

Each playable race has a unique self-buff spell that defines their magical identity. These spells are excellent inspiration for:
- Naming magic tactics in raids related to that race
- Understanding what "casting" feels like for that race
- Building thematic bonus structures (e.g., a magic tactic that benefits races whose spell aligns with the raid's theme)

| Race | Spell Name | Effect | Flavor |
|---|---|---|---|
| Dark Elf | **Spellwright's Calling** | Wizard Guilds produce military units; +5 raw mana/guild | Calling warriors through arcane guilds |
| Demon | **Infernal Command** | Offense boost from pairing; -20% demon casualties; applies Corruption | Command through demonic hierarchy |
| Dwarf | **Miner's Sight** | +20% ore production; ore immune to damage | Seeing through stone to rich veins |
| Firewalker | **Alchemist Flame** | +12 raw platinum/hr per alchemy | Transmuting fire into wealth |
| Gnome | **Miner's Sight** | Same as Dwarf | Mechanical mining augmentation |
| Goblin | **Killing Rage** | +10% offense | Pure bloodlust and frenzy |
| Halfling | **Frenzy** | +20% spy power, -10% spy losses | Entering a dangerous stealth frenzy |
| Human | **Crusade** | +10% offense | Holy/Imperial war drive |
| Icekin | **Alchemist Frost** | +15% platinum production | Freezing wealth into permanence |
| Kobold | **Howling** | +10% offense and defense | Pack howl empowering the swarm |
| Lizardfolk | **Erosion** | Auto-rezones 20% of land to water | Reclaiming the land for the ocean |
| Lycanthrope | **Feral Hunger** | Converts 16 enemy units per 100 sent | The hunger that makes werewolves |
| Merfolk | **Erosion** | Same as Lizardfolk | The tides reclaiming the shore |
| Nomad | **Favorable Terrain** | +offense from barren land | Reading the open plains for advantage |
| Orc | **Bloodrage** | +10% offense, +10% offense casualties | Berserk fury accepting losses for power |
| Spirit | **Unholy Ghost** | Ignores draftee requirement for ops | Moving through the world unseen |
| Sylvan | **Verdant Bloom** | Auto-rezones 35% of land to forest | Gaia's growth reclaiming the land |
| Troll | **Regeneration** | -25% all casualties | The trolls simply refuse to stay dead |
| Undead | **Death and Decay** | Decays food and lumber; converts peasants to zombies; cooldown 24h | The creeping death that makes more undead |
| Vampire | **Feast of Blood** | Cancels immortal units; converts 10 units per 100 sent | The blood feast that spreads the curse |
| Wood Elf | **Gaia's Light** / **Gaia's Shadow** | Light: +30% wizard, -10% spy / Shadow: +30% spy, -10% wizard | Choosing between light and shadow aspects |
| Nox (NPC) | *(none in data)* | — | Darkness manipulation; void magic |

**Using Racial Spells in Raids:**
- A magic tactic named "Cast Bloodrage" would feel authentic in an Orc-themed raid and could grant the Orc race a bonus
- "Invoke Verdant Bloom" fits a nature-restoration objective and would give Sylvan a bonus
- "Channel Feral Hunger" in a lycanthrope-infested raid gives Lycanthrope/Evil alignment races a bonus
- The contrast between **Gaia's Light** and **Gaia's Shadow** for Wood Elves makes for interesting dual-path raid choices
- **Death and Decay** (Undead) with its 24-hour cooldown and peasant conversion makes undead raids particularly dangerous — the Undead are actively growing their forces during combat

---

## 20. Potential Future Story Threads

Based on the accumulated lore across all rounds, the following unresolved threads could inspire future raids:

### Immediate Threads (Round 72 arc fallout)
- **The Planewalker Retreated**: The Planewalker used `wounded_retreat` — it did not die. It stepped back through the shattered Gates, meaning it still exists somewhere. A follow-up raid could involve pursuing it through a partial Gate.
- **The Planar Gates Are Still Active**: The Gates wonder is currently active in the game (+1 free tech). What other dimensions might be pressing through now that the Gates are known to work?
- **Eris Squigglereach's Research**: The slain Gnome scientist's work on "time-matter manipulation" and "bending reality" is left incomplete. Another faction might attempt to finish it — or weaponize it.

### Mid-Term Threads
- **Nox Remnants**: The Lich King was defeated but the Nox are an entire race. Scattered cults still exist in the swamps. A lesser Nox warlord rising with a fragment of the Lich King's power is a natural next step.
- **The Lycanthrope Bite Spreads**: Lycanthrope lore says "once bitten, you'll never be the same again." A plague of lycanthropy spreading through a major city — with the player having to choose between a cure (invest mana) and a cull (invasion) — is rich territory.
- **Urg, the Devourer Returns**: Urg is dormant (`active: false`) but his 1,000,000 HP wonder and daily attacks make him the most dangerous single entity in the game. A raid where realms must collectively damage Urg enough to drive him into dormancy — using every tactic type simultaneously — would be mechanically novel.
- **Dark Elf Corruption Spreads**: Dark Elves were "corrupted long ago by whispered promises of power from demonic entities." That corruption might have a source that was never sealed.

### Long-Term / Epic Threads
- **The Void Expands**: The Planewalkers came through a void portal. The void itself — the dimension between planes — may have its own inhabitants beyond Planewalkers. A multi-round story arc where the void begins consuming the world's edges.
- **The Undead-Vampire Alliance**: Both Undead and Vampire occupy swamp land, both have conversion mechanics, both have immortal wizards. A political union of these two factions against the Empire would be a major geopolitical crisis.
- **The Sylvan-Orc Conflict**: Sylvan actively expand forests (+35% auto-rezone); Orcs depend on lumber (+50% lumber production) and have home land in forests. A territorial war between these two races over the great forest is a classic ecological conflict.
- **Planar Ecosystem Collapse**: If too much dimensional energy was released through the Gates, planar creatures — not just Planewalkers — might begin bleeding through. Each round of raids fights a different planar species drawn to the Gates' residual energy.

### Thematic / Standalone Raids
- **The Temple Wars**: Temples reduce enemy defense; control of a great temple complex could justify both espionage (theft of holy relics), magic (desecration or sanctification), investment (rebuilding after an attack), and invasion.
- **The Grand Tournament**: A rare non-military raid — a realm-vs-realm competition where the victory conditions are prestige and glory rather than survival. Heavy hero and exploration focus.
- **Famine Crisis**: Farms are burning, food stores are depleted (perhaps by the Undead's Death and Decay or the Insect Swarm spell gone wild). Realms race to restore food production — heavy investment (food resources) and magic (Harmony spell, Gaia's Watch) focus.
- **The Sunken City**: Lizardfolk and Merfolk have been aggressively rezoning land to water (Erosion spell). A city has literally sunk beneath the waves. Heroes dive into the drowned ruins, spies steal waterproofed artifacts, mages channel water control spells.
- **Gnomish Catastrophe**: A Gnomish invention has gone horribly wrong — their Mining Machines have destabilized a mountain range, threatening an avalanche that will bury three realms. The race to stabilize the underground before the collapse is an engineering-focused, investment-heavy, exploration-rich raid.

---

## 21. Canonical Raid Descriptions — Style Reference

The following are the actual in-game descriptions for every raid produced to date, presented as a style reference. Note tone, sentence rhythm, paragraph structure, escalation of stakes, and how each description earns its call-to-action.

---

### Bandit Encampments *(Round ??, standalone intro)*

> The Emperor's patience has reached its end. Bandit attacks across the Empire have intensified dramatically. These are no longer isolated incidents but a coordinated assault on Imperial authority. A substantial bounty has been offered for every bandit camp destroyed.

**Notes:** The shortest possible raid description — four sentences. Establishes the Emperor's authority, frames the threat as systemic rather than random, and closes with a concrete reward. No lore worldbuilding needed for a simple bandit raid.

---

### Lair of the Dragon *(Round 68, Part 1)*

> A great wyrm has terrorized the countryside for months, growing bolder with each passing moon. What started as occasional reports of missing livestock quickly escalated: first granaries consumed by dragonfire, then entire merchant caravans reduced to ash. Now whole villages lie in smoldering ruin, their people displaced or devoured. Survivors speak of seeing the beast disappear into the treacherous mountain peaks to the north.
>
> His lair is rumored to be piled high with luminous gemstones, ancient artifacts from fallen kingdoms, and enchanted weapons from heroes who tried and failed to slay the beast. Meanwhile, his drake-spawn multiply in the surrounding caves, filled with the charred remains of their prey. The dragon's death would mean not just freedom from terror, but wealth beyond imagination for those brave enough to claim it. Already, kingdoms mobilize their forces, each hoping to lay claim to the dragon's hoard.

**Notes:** Two-paragraph structure. Paragraph 1 = escalating threat (livestock → caravans → villages), ending on a location hook (the northern peaks). Paragraph 2 = reward motivation. The phrase "heroes who tried and failed" raises stakes without saying anyone died explicitly. Competitive urgency ("kingdoms mobilize") closes the description.

---

### Ironhold Citadel *(Round 68, Part 2)*

> One of the Emperor's most trusted advisors has committed the ultimate betrayal. In a single bloody night, he murdered a high-ranking Imperial official, plundered the Imperial treasury, and fled to his ancestral kingdom. From there, he commands Ironhold Citadel, a fortress which has never fallen to siege in eight hundred years of war.
>
> Intercepted letters reveal the depth of this conspiracy: years of secret correspondence with rival kingdoms and promises of imperial territories in exchange for military support. The traitor now raises his banner in open rebellion, declaring the Emperor a tyrant and calling for others to join his cause. The Emperor has commanded the realms to crush this insurrection and deliver the traitor's head before the next new moon. Those who answer this call to arms will share the spoils from the stolen Imperial treasury.

**Notes:** Political betrayal arc — continues Round 68's story without being a supernatural sequel. "Never fallen to siege in eight hundred years" sets up the citadel as a genuine challenge. The intercepted letters detail is a nice espionage flavor beat. Hard deadline ("before the next new moon") creates urgency.

---

### The Island Fortress *(Round 69, Part 1)*

> A defiant lord has fled to the Shattered Isles, establishing a fortress on one of the thousand unnamed rocks that dot these treacherous waters. From this hidden stronghold, his corsairs raid Imperial shipping with impunity, vanishing into the maze of reefs and fog before pursuit can be organized. The rebels grow bolder each day, their stolen warships multiplying in hidden harbors.
>
> The Shattered Isles are a navigator's nightmare — unmapped channels, shifting sandbars, and jagged reefs that have claimed countless vessels. Storm clouds gather perpetually overhead, and strange currents pull ships toward rocky doom. Somewhere in this labyrinth, the traitor lord sits secure behind stone walls, convinced the Empire's forces will never find him.
>
> The Emperor demands this nest of rebels be destroyed before their piracy cripples Imperial trade. Those who answer this call must first build a fleet capable of navigating the deadly waters before attempting to assault the hidden fortress. The rebels' accumulated plunder awaits those brave enough to claim it — but first, you must find them.

**Notes:** Three paragraphs — threat, environment, call to action. The middle paragraph is entirely devoted to making the location feel dangerous and real. "Convinced the Empire's forces will never find him" sets up the satisfying inversion of his assumption. The multi-stage structure ("first build a fleet... then find them") mirrors the raid's mechanical objectives.

---

### The Tomb of Kings *(Round 69, Part 2)*

> For a thousand years, the Tomb of Kings has stood sealed — a vast necropolis where the Empire's ancient rulers were laid to rest with their armies, treasures, and most powerful artifacts. But grave robbers have broken the great seal, and in their greed for gold, they have awakened something that should have slept forever.
>
> The ancient kings do not rest easy. Awakened by the desecration, they rise as powerful undead lords, each still commanding the loyalty of their entombed armies. Skeletal legions rise from the catacombs, bronze armor gleaming, while spectral knights advance riding phantom steeds. These undead forces march with purpose, seeking to reclaim lands they ruled in life.
>
> Most terrifying of all, something stirs in the deepest vaults — an ancient evil imprisoned for centuries. If it fully awakens before the tombs can be resealed, the dead shall outnumber the living, and the Empire will become a kingdom of graves. The Emperor commands immediate action: enter the necropolis, destroy the risen dead, and seal the tombs before the ancient darkness escapes its prison.

**Notes:** Classic escalation structure. Each paragraph raises the stakes: sealed tomb → risen undead lords → something worse below. "The Empire will become a kingdom of graves" is a strong apocalyptic closer before the call to action. The inciting incident (grave robbers) humanizes the cause without needing a villain.

---

### The Nightbringer's Rise *(Round 70, Part 1)*

> An ancient evil has reawakened. From the void beneath the realms, the skittering Nox pour upwards through the swamps. Behind them trails a veil of darkness — an impossible cloak swallows the sun itself. In this new, perpetual alien twilight, there is a new star that burns brighter than the others. And as the days drag on, it grows brighter. And brighter. A comet — and it is heading straight for us.
>
> There are long-forgotten tales of such an event; a mad Nox Lich King, who wanted to blot out the sun forever, who summoned a great meteor to smash into the earth, drowning the sun in dust. And now, it seems he has returned.
>
> The Nox revere him as a god. They call themselves the Cult of the Nightbringer. They dance around strange, whispering pillars of cold light that stretch up into the night sky. As if beacons, calling the comet home, ushering in a new age of darkness and ruin.
>
> This is a threat unlike anything the realms have ever seen before. You face not just something ancient and evil, but existential. The end of light. The end of all life. Act now, or your annihilation is assured.

**Notes:** The game's most ambitious raid description — four paragraphs, cosmic stakes. Paragraph 1 uses short, punchy sentences and deliberate repetition ("brighter. And brighter.") to build dread. Paragraph 2 ties to ancient lore. Paragraph 3 introduces the Nox cult with vivid imagery (the whispering pillars). Paragraph 4 drops all subtlety: this is existential. The direct address ("You face") is used only here — appropriate for the game's highest-stakes moment. Fragment sentences ("The end of light. The end of all life.") for rhetorical punch.

---

### The Lich King's Fury *(Round 70, Part 2)*

> It wasn't enough. You defeated the Nightbringer and thinned the Nox cultists' numbers, banishing the alien darkness. But the comet remained. Still hanging in the sky, like a second sun, burning ever brighter as the days went by. You'd bought time, weeks instead of days.
>
> The Emperor convened the races. He lauded the efforts of the realms so far, but the fight was far from over. It was time to do the unthinkable.
>
> With the combined resources of every dominion in the land, they could excavate the ruins of the Planar Gates — the portals of transportation once used by the despicable Planewalkers in their bid to conquer the realms. This forbidden technology, said the Emperor, could be repurposed. With the channeled magic of every last mage in the land, they could teleport the comet to the far side of the known realms, letting it pass by harmlessly.
>
> But the Lich King will not stand idly by as they rebuild the technology of his oldest enemies. The Emperor warned that they would have to take the fight to the void beneath the realms, face the mad king himself, and end this — once and for all.

**Notes:** Masterful sequel hook. Opens with "It wasn't enough" — immediate acknowledgment of the players' prior effort, immediately undercut. "Like a second sun" reframes the comet as a visible dread presence. The plan (Planar Gates) is explained with enough technical detail to feel earned. The final paragraph reintroduces the villain as an active obstacle. "Once and for all" is a classic closer that signals finality.

---

### The Gods' Ultimatum *(Round 71, standalone)*

> The heavens split open at dawn, and the voices of the gods thunder across every realm simultaneously. They speak of ancient pacts made when mortals were first given the gift of magic. The time has come to pay the debt. The gods demand a tithe of life itself — mortal souls offered willingly upon their altars.
>
> This is no threat, but a transaction. Those who feed the divine hunger will receive fragments of creation's blueprint — knowledge that could advance civilization by centuries. Those who refuse will receive nothing, while their rivals grow wise on sacred secrets. The altars have risen from the earth, waiting. The choice is yours: preserve your people, or purchase power with their lives.

**Notes:** The game's only morally ambiguous raid — and the description reflects it. "This is no threat, but a transaction" is the key line; the gods are not villains. The competitive framing ("while their rivals grow wise") makes inaction feel costly without making sacrifice feel heroic. Notably, the Emperor does not appear — no authority commands the player. The choice is genuinely theirs.

---

### Planewalker Incursion *(Round 72, Part 1)*

> Some called it madness to excavate the Planar Gates — no matter the threat. But excavate them the Emperor did. Together with immense magic, the realms had moved the heavens — transporting a comet on a direct collision course from one side of the planet to the other. The technology of the Planar Gates had saved the world.
>
> But the Gates were also the walkways of the deadliest enemy the realms had ever known: the dimension-bending Planewalkers.
>
> To unearth their long-buried relic was dangerous enough, but to use them... it invited disaster. No one truly knew how the Planar Gates worked. Even shattered as they were by the sheer ferocity of the magics poured into them to displace the comet, the potential for the technology to jumpstart research projects was immense. It had been centuries since anyone had even seen a Planewalker. No one knew what had become of them, or indeed if any still lived.
>
> And so the Emperor sanctioned further research — and it was that fateful decision that led to the unfortunate end of Eris Squigglereach, a renowned Gnome scientist. It was his efforts that led to the first breakthroughs that proved the Gates were more than just teleporters, capable of time-matter manipulation, and perhaps even a lens to bend reality itself.
>
> Eris had tried to raise the alarm when the broken segments of the Gates started to reactivate on their own. No tests were scheduled. They weren't powered. But Eris hadn't been quick enough. And as a result, he and his research team were the first souls in centuries to have the misfortune of seeing a Planewalker face-to-face, as it stepped through the shimmering pool of light from the Planar Gates and into our dimension. It took a moment to survey the scene, assessing the room full of shocked faces. Then it sneered, its blue-grey face twisting in contempt. That was the last thing Eris Squigglereach ever saw.
>
> The massacre began.

**Notes:** The game's longest raid description and its most novelistic. Five paragraphs of rising dread before the inciting incident. Eris Squigglereach is the game's most developed named NPC — his death is given space and detail that makes it land. "The massacre began." as a standalone final sentence is the guide's best use of a paragraph break for dramatic effect. The Planewalker's physical description ("blue-grey face twisting in contempt") is the only moment any raid enemy is described visually. Use this as the ceiling for how cinematic a raid description can get.

---

### Rise of the Hydromancer *(Round 72, Part 2)*

> A lone Planewalker stepped through the reactivated Planar Gates and it was enough to plunge the entire realm into chaos. It seemed to possess limitless magic, conjuring an army of Golems hewn from rock and vine and mud, dredged up into existence from the very earth itself. These slow but powerful monsters were resistant to all forms of damage — except, it seemed, for water. Hydromancers, a long derided subclass of mage and oft the butt of many a joke, suddenly became the most sought-after specialists in the lands. Where steel and fire failed, their Great Floods swept entire battalions of Golems away.
>
> From the few direct encounters with the Planewalker itself, survivors told of a being that moved at lightning speed, leaving a trail of light reminiscent of the faint blue glow of the Planar Gates portal. It moved like the wind, a blur of light cutting soldiers down before they could draw their weapons, indeed before many could so much as blink. Not a single soldier managed to lay a finger on the ethereal creature.
>
> Although little was known about this ancient foe, historians were clear on one point: They are mortal. They bleed, they die. They were driven back once, by overwhelming numbers. And this time, we have their technology. By studying it, we can find a way to predict the Planewalker's movements, understand its deadly speed, and perhaps give our heroes the chance they need to strike it down.
>
> This will be no simple fight.

**Notes:** Closes the Planewalker arc with a description that is equal parts briefing and rallying cry. The Hydromancer detail is excellent worldbuilding — a mocked specialist suddenly vital, which rewards players who chose water magic builds. The Planewalker's speed is described through survivor accounts rather than direct omniscient narration (same technique as the Nightbringer). "They are mortal. They bleed, they die." — three short declarative sentences as a turning point. "This will be no simple fight." is the game's most understated closer, appropriate for the hardest encounter.

---

### Style Takeaways Across All Raids

| Technique | Example |
|---|---|
| **Escalation within a paragraph** | Bandit livestock → caravans → villages |
| **Short sentence for impact** | "The massacre began." / "It wasn't enough." |
| **Survivor accounts for monster description** | Planewalker's speed; Nightbringer's darkness |
| **The Emperor as authority** | Present in political/military raids; absent in The Gods' Ultimatum |
| **Named NPCs** | Eris Squigglereach — the only named victim; makes stakes personal |
| **Location as character** | The Shattered Isles paragraph in Island Fortress |
| **Moral ambiguity** | Gods' Ultimatum is the only raid with no "right" answer |
| **Competitive framing** | "Already, kingdoms mobilize" / "while their rivals grow wise" |
| **Direct address** | Used sparingly — only in Nightbringer and Gods' Ultimatum |
