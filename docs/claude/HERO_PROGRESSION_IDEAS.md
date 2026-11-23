# Hero Progression: Ideas, Decisions and Balance Notes

A record of the hero combat and progression design work: what was built, what was discussed and set aside, and what's still open. For how the engine works, see [HERO_COMBAT.md](HERO_COMBAT.md).

Balance numbers come from `php artisan hero-combat:simulate`: Tactician vs Tactician, a level-9 hero with only the pick against a basic level-9 hero, with sides swapped halfway over the same seeds. Scores count wins plus half of draws; 50% means no edge. With 400 battles the margin of error is about ±2.5%.

---

## Built

| Area | What it is | Branch |
|---|---|---|
| Combat engine | Modular `src/HeroCombat`: simultaneous turns, N-vs-N teams, effects with duration/stacks/tags, ordered event hooks, team and field effects, a single damage pipeline, cooldowns and charges, seeded replays (`hero-combat:replay`) | `feature/hero-combat-v2` |
| Content port | 5 basic actions, class abilities and passives, all 18 original encounters (the unused wolf/bandit/imp/cultist "story" encounters were skipped) | `feature/hero-combat-v2` |
| Daily PvP XP | Flat 25 XP once per round day, win or lose, if your time bank never ran out | `feature/hero-combat-v2` |
| Techniques | Passive rules: Technique I (level 3) and Technique II (level 7), replacing Disciplines and Combos | `feature/hero-progression` |
| Shared statuses | Exposed, Marked, Staggered (plus Invigorated from Second Wind), cashed in by any attacker | `feature/hero-progression` |
| Class Abilities | Any class signature, available at class level 5 (active or not); uses the class icon | `feature/hero-progression` |
| Gear | The only source of combat stats: one piece at level 3, a second at level 7 (moved down from 9 and 12 for release 1); gear can't take evasion below 0 | `feature/hero-progression` |
| Combat loadout | Techniques, abilities and gear left the upgrade system. Access is automatic; the hero brings 1 technique, 1 ability and 2 different pieces of gear, changeable at any time | `feature/hero-progression` |
| Upgrade schedule | Dominion upgrades only: level 1 doctrine, then levels 2, 4 and 6. Data-driven slots. | `feature/hero-progression` |
| Ally actions | Guard (Defend on an ally), Rally (Focus on an ally), healing an ally | `feature/hero-progression` |
| Simultaneous resolution | Damage and healing land together before anyone is defeated; the order of combatant ids decides nothing | `feature/hero-progression` |
| Borrowed heroes | Pilot a realmmate's hero in party-enabled encounters, live or practice; no owner reward | `feature/hero-progression` |
| Tactician + simulator | Game-theory AI and `hero-combat:simulate` for PvP balance. Analysis only, not in the game. | `feature/hero-progression` |
| Release gating | `app/config/heroes.php`: `loadout_slots` (gear only for release 1; techniques and abilities show as coming soon) and `daily_pvp_experience` (off) | `feature/hero-progression` |
| 2v2 PvP queue | Queue for 1v1 or 2v2. A 2v2 is two players, each piloting their hero plus an identical templated Sellsword (base stats at the lower hero's level, no credit). The battles page shows whether someone is waiting and polls for changes for 2 minutes | `feature/hero-progression` |

Class abilities and passives reach heroes only through the loadout; `HeroClassLoadouts` no longer grants them by active class. Some are also used by PvE enemies (Shadow Strike; the old Hardiness lives on for enemies as Cling to Life).

---

## Redesign: 2v2 Rules (built)

Measured with `scripts/hero-combat-sim` before anything was built: base actions only, level 9 (105 HP), near-optimal play, 2 seeds per ruleset. See HERO_COMBAT.md for how the simulator works. 1v1 numbers are exact solutions.

| Rule | Decision | Why |
|---|---|---|
| Resolution | Fully simultaneous | Required: a kill isn't a kill if you're healed on the same turn. Sequential resolution also gave the lower-id team about 53.5% |
| Recover | 3 charges per battle, shared with healing an ally, in all battles | Breaks the stalemate. Bosses that need more healing get redesigned |
| Guard | +50% defense instead of doubled, no streak penalty | At 40 defense it blocks every unfocused hit; charges alone left 70–78 turn battles |
| Repeat Defend | Exposed, now −25% defense: 20 × (2 − 0.25) = 35 | About 5 turns shorter and no stalls. Exposed was first a flat −5 (30); switching to −25% (35) changed nothing measurable (1v1 14.0 vs 13.4 turns, 2v2 16.9 vs 16.5, same options and Defend use). A Defend-only cooldown tied in 2v2 (5.12 viable) and held up in 1v1 (3.06 viable, 12.8 turns) but raised 1v1 draws to 38.5%. Sharing it with Guard cost options (4.90) |
| Rally | Focus on an ally: +focus to their attack this turn, sharing Focus's cooldown | The only change that adds real options |
| Counter | Fires through a guard | Small gain (5.10 vs 5.03 viable), and it's what players expect |
| Recover | Leaves the healer Exposed (−25%), replacing its own −5 penalty | One status and one rule for every defensive penalty |
| Mutual Guard | Hits swap; both keep +50% | A real choice when one hero is hurt, with no measurable cost (5.09 vs 5.10 viable) |

### 2v2 (simultaneous)

| Ruleset | Turns | Draws | Stalls | Viable / legal |
|---|---|---|---|---|
| Both limits (Guard 30, 3 charges) | 22–26 | 11–12% | 1.4–2.8% | 4.47–4.69 / 6.08 |
| + Rally | 21.7 | 9.9% | 1.7% | 5.00 / 6.92 |
| + Exposed 30 | 16.2 | 8.6% | 0% | 5.03 / 7.04 |
| + Counter through guards | 16.4 | 8.9% | 0% | 5.10 / 7.04 |

The two seeds of "both limits" split into an attack-heavy and a Defend-heavy style (Defend 8% vs 31%), hence the ranges. With Exposed they agree closely.

Sequential runs, kept for the single-limit results: Guard 30 alone stalled 96% of battles; charges alone ran 70–78 turns with Guard at 41% of play. A guard streak penalty cost 0.25 viable options and wasn't needed.

### Ability balance (final rules)

| Ability | 2v2 chosen | 2v2 viable | 1v1 chosen |
|---|---|---|---|
| Attack | 43.1% | 99.5% | 41.5% |
| Counter | 17.3% | 95% | 22.3% |
| Heal ally | 9.2% | 56.5% | n/a |
| Guard | 8.5% | 82.5% | n/a |
| Recover | 7.5% | 52.5% | 11.6% |
| Focus | 6.6% | 57.5% | 10.5% |
| Defend | 5.0% | 65.5% | 14.1% |
| Rally | 3.1% | 62% | n/a |

Focus and Rally running low is accepted: they're specializations for techniques and gear to promote. Defend isn't weak either: Defend plus Guard is 13.5% of 2v2 play against Defend's 14.1% in 1v1, so it's the same job split across two buttons. Healing's viability is understated because it counts turns near full health. Removing Exposed would take Defend to 9.1%, at the cost of 5 more turns and some stalls.

### 1v1 (exact)

| | Before | 3 charges | Charges + Exposed, simultaneous |
|---|---|---|---|
| Turns | 22.8 | 16.2 | 13.4 |
| Defend | 44.2% | 16.9% | 14.1% |
| Moves played 5%+ | 1.99 | 2.64 | 2.57 |
| Viable options | 3.10 | 3.19 | 3.10 |
| Draws | 33.9% | 34.2% | 34.9% |

The earlier 1v1 scores in this document predate these rules and need fresh numbers.

### Snowballing (parked)

The first kill wins about 99.5% of decided battles under near-optimal play. Survivor buffs tested on the proposal: +10 or +20 attack mostly created draws; +10 defense brought back stalls and 35-turn battles; a 30 heal only delayed the loss; +5/+5 gave 95% at 4.90 viable; +10/+10 gave 80% (one seed) but 24% draws, 4.51 viable, and made losing an ally less of a punishment. Set aside in favor of ability balance.

---

## Redesign: Class Abilities, first pass (built)

Measured with `scripts/hero-combat-sim` under the 2v2 rules above. One hero has the ability; everyone else is a base hero. Scores are 2 seeds, given as points above a hero with one Greatsword (+2 attack, −10 evasion) in the same format, because health equivalents mislead in 2v2 (the 2v2 health curve is flat and lumpy: +5/+10/+15/+20 scored 54.2/57.5/59.1/66.2%). One Greatsword is worth about +7 health in 1v1 (58.0%) and about +8 in 2v2 (56.2%).

| Decision | Detail |
|---|---|
| Abilities never replace a basic action | Six usable buttons for everyone. Replacing Defend or Recover measured badly: Fortify replacing Defend was worth nothing in 2v2 (it costs Guard), and replacing Recover was worth nothing in 2v2 (it costs healing an ally) |
| Target power | Clearly above one gear piece, about 5 points, in both formats |
| Variety over one-shots | A recurring choice beats a single charge used once per battle |
| No defense that walls a Focused attack | Defense buffs get multiplied by Defend (×2) and Guard (+50%), so any sizeable buff that stacks with a stance blocks Focus and slows battles |

| Ability | Final design | 1v1 | 2v2 | Notes |
|---|---|---|---|---|
| Fortify (Architect) | +10 defense on you or an ally for 3 turns, not while Defending or Guarding; not twice in a row; a recast starts the turns over | +5.9 | +7.6 | Adds options; battles run longer (about 18 turns in both) |
| Caltrops (Engineer) | On you or an ally until struck; each attacker that turn takes 15 flat | +4.9 | +5.3 | Adds options; weakens Defend-heavy play |
| Shadow Strike (Infiltrator) | Can't be evaded or Countered; 5 less damage | +5.0 | +0.6 | Battles slightly shorter; its PvE job is evasive bosses |

Tried and rejected, 1v1 / 2v2 against Greatsword unless noted:

| Idea | Result |
|---|---|
| Fortify as today (20 shield, replaces Defend, unlimited) | Worth over +20 health in 1v1, fewer options |
| Shield with charges (shared with Recover, own 1, own 2) | +2 to +10; one charge is a single decision per battle; a shield that shares Recover's charges loses to the heal |
| Shield that starts the battle (passive) | Gear that can't be healed; not tested |
| Permanent defense (+3 or +2 per cast) | +3: +6.4 / +16.8 and long 2v2 battles; +2: −1.3 in 1v1 |
| Timed +5 buff | −6 to −8 in 1v1: a turn is worth about 20 damage, +5 defense saves less |
| Timed +10 buff stacking with stances | +2.6 / +13.1; blocks Focus under Defend |
| 10-point shields | Not tested: can't pay back a turn |
| Caltrops (Snare) at 10 / 20 | 10: −4 / −4, the most options; 20: +15 / +20 |
| Caltrops split (15 alone, 10 each for several) / one at a time | −1.6 points / no change in 2v2 |
| Foundations (no Exposed from repeat Defend) | −8.4 / −6.1, longer battles, a few stalls |
| Masonry (half the Focus and Rally bonus against you) | +1.3 / −2.9: enemies just focus the partner |
| Shadow Strike as it was | −3.3 / −3.5 |
| Shadow Strike un-Counterable at full damage / at −10 | +14.3 / −3.7 in 1v1 |

Snare was renamed Caltrops because Magic Snare is an espionage black op. The old Fortify shield is kept for enemies as Stone Shield.

### Class techniques (built)

Mending, Channeling and Hardiness are the Healer's, Sorcerer's and Farmer's class unlocks in the Technique slot (class level 5). Scion keeps Last Stand in the Ability slot, not yet reviewed. Target: about one gear piece, since the old technique measurements put the best Technique I picks at gear level (55–60% against a base hero) and the rest below. Measured under the 2v2 rules above, points against one Greatsword:

| Option | 1v1 | 2v2 | Notes |
|---|---|---|---|
| **Mending: +10 healing while Focused, keeps the Focus (built)** | −2.9 | +1.4 | Better in 2v2, where Heal goes to the ally |
| Mending as it was (+10, spends the Focus) | −7.0 | | Worth nothing: still costs the Focus turn |
| Mending +13 / +15, keeps the Focus | +2.8 / +5.5 | +10.2 / +9.6 | Cliff in 2v2; battles about 2 turns longer |
| Mending +20, keeps / spends the Focus | +16.9 / −1.8 | | |
| Channeling as it was (Focus stacks) | −5.3 | | A stacked Focus is visible, so opponents Defend |
| Channeling: Focus again the next turn | −4.7 | | Faster stacking doesn't help |
| Channeling: Focused attacks ignore 5 / 10 / 15 / 20 defense | 0.0 / +6.1 / +17.8 / +26.0 | | The same as more focus value against any normal target |
| Channeling: Focused attacks ignore a quarter / a third / half of defense | +1.6 / +1.8 / +5.6 | | Grows against Defenders; opponents Defend less |
| Channeling: can't be blocked by Defend or Guard / can't be Countered | −5.0 / −3.0 | | Opponents already answer Focus with Counter, which also answers normal attacks |
| Channeling: 2 / 3 stacks ignore all defense | +4.8 / −3.9 | | 2 stacks: fewer viable options (2.75) |
| Channeling: each stack ignores a quarter, up to 2 | +3.6 | | Adds a release-or-charge decision |
| **Channeling: a Focused attack Exposes the target (built; game rules, Greatsword 57.5% / 57.5%)** | +0.1 | −0.6 | Exposed adds with Defend (35 against a Defender, not 30), and the ally's attack that turn benefits. In 2v2 the Channeling team Focuses more (7.9% vs 6.0%, viable 63% vs 51%), opponents Defend and Counter a little less, and teammates hit the Exposed target together slightly more |
| **Volatile Mixture as it is (150%, 20% backfire), kept** | +1.1 | +1.6 | Gear level, kept as the gambler's choice; the backfire no longer lands on a guarding ally |
| **Hardiness: +5 defense at 40 health or less, not while Defending or Guarding (built)** | +4.1 | +1.0 | Weaker in 2v2: a low hero gets finished or healed |
| Hardiness: +2 / +3 at 40 health | −1.1 / +1.8 | — / −0.4 | |
| Hardiness: +5 at 30 / 25 health | −0.2 / −3.9 | | |
| Hardiness: +10 / +15 at 40 health | +25 / +40 | | Halves every hit when low; +10 stacking with Defend stalls (7%) |

### Class abilities, second pass (built)

Under the game's current rules (Greatsword 57.5% in both formats). A turn spent on Forge or Combat Analysis gives up about 20 damage, so the lasting bonus has to beat that: +1 never did.

| Option | 1v1 | 2v2 | Notes |
|---|---|---|---|
| **Forge +4 attack, 2 uses (built)** | +2.2 | +2.7 | Only helps its user; holds steady across formats |
| **Combat Analysis −4 defense, 2 uses (built)** | +2.5 | +2.4 | Pulls the team onto one target (81% same-target vs 76%), fewer viable options (4.6 vs 4.9) |
| Forge +1 / +2, unlimited | −6.7 / −5.4 | | Almost never used |
| Combat Analysis −1, unlimited | −6.6 | | |
| Forge +4 x3 / +5 x2 / +6 x2 / +8 x1 | +1.7 / +7.1 / +9.6 / +15.0 | | A third use barely matters |
| Combat Analysis −2 x2 / −3 x2 / −3 x3 / −5 x2 | −4.4 / −1.6 / −0.7 / +7.8 | | |

Scion's Last Stand has been removed; the Scion has no signature until a new idea is chosen.

### Abilities for every class (built)

| Ability | Final design | 1v1 | 2v2 | Notes |
|---|---|---|---|---|
| Blade Flurry | 2 hits, each at 85% of the attack value (Focus and Rally included) before defense; one evasion roll; a Counter answers each hit; not twice in a row | +5.2 | +8.1 | Rewards setups (Focus, Rally, Exposed); weak against Defend |
| Whirlwind | Every enemy for 70% of normal damage after defense; each enemy can Counter; not twice in a row | about −8 (≈50%) | +0.3 | Meant for PvE; gear-level in 2v2 by design, since an AoE can't be balanced in 1v1 |

| Idea | Result |
|---|---|
| Flurry 2 × 0.75 / 0.7 / 0.65 after defense | 1v1 +10.5 / +5.4 / +3.3; 2v2 0.7 +6.3, 0.65 +4.3. Rejected: after defense it's just a stronger normal hit |
| Flurry before defense 0.75 / 0.8 / 0.95 | 0.75: −5.5 in 1v1; 0.8: −1.0 in 2v2; 0.95: +20 in 1v1. Each 0.05 is about 4 damage per use, so the steps are coarse |
| Whirlwind un-Counterable 0.75 | +1.2 in 1v1, +33 in 2v2 (89.4%) |
| Whirlwind Counterable 0.6 / 0.65 / 0.75 (2v2) | −6.1 / −4.6 / +6.0 |
| Whirlwind before defense | Not tested: at the same percentage it's far weaker against defense, and spiky with Focus; it fits Flurry's role, not an AoE's |

---

## Redesign: Combat Loadouts (built)

Techniques, class abilities and gear were permanent one-time picks taking up the odd upgrade levels. They are now loadout options: unlocked automatically and swapped freely on the heroes page.

| Slot | Capacity | Access |
|---|---|---|
| Technique | 1 | Level 3 (Technique I), level 7 (Technique II); Mending (Healer) and Channeling (Sorcerer) at class level 5 in that class |
| Ability | 1 | Class signature: class level 5 in that class, active or not, no hero level requirement. Blade Flurry and Whirlwind: level 5, any class |
| Gear | 2 different pieces | Level 3, the second piece at level 7 |

Definitions moved from `heroes.yml` to the combat registry (`HeroLoadoutOptions`): the behavior, names and descriptions were already there, the simulator needs the data without a database, and gear from other sources can join through `HeroCalculator::getAvailableLoadoutOptions()`.

The sections below were written for the upgrade version. Their rule descriptions and measurements still apply, but the pick schedule and "one pick per tier" have been replaced by the loadout rules above.

### Two gear pieces (vs basic, 400 battles, seeds 101 / 5001)

| Gear | Score | Average turns |
|---|---|---|
| Greatsword + Plate Armor | 71.8% / 70.1% | 17.2 / 17.8 |
| Greatsword + Vestments of Renewal | 74.6% / 71.2% | 16.0 / 16.9 |
| Plate Armor + Vestments of Renewal | 67.5% / 69.9% | 24.2 / 25.9 |
| Swordbreaker Dagger + Hooded Cloak | 65.2% / 66.5% | 21.0 / 21.0 |
| Crystal Wand + Hooded Cloak | 62.4% / 68.2% | 17.5 / 16.8 |

A single piece scored 57-61%, so two pieces are well above the 55-60% target. Gear values stay as they are until the whole build (technique, ability and gear) is measured.

---

## Redesign: Techniques and Class Abilities (built)

Disciplines and Combos merge into **Techniques** (passive rules). Class abilities and passives become **Class Abilities**, unlocked by class level. The Combo type and its machinery (`AbstractCombo`, `ComboStatus`, the Combo badge in `BattleView`, `combo=` in the simulator) go away.

Why: every Combo was "after action X, +N to one stat next turn", which is gear with a condition, not a rule. Spellweave and Wellspring never found a payoff worth taking.

### Design rules

| Rule | Detail |
|---|---|
| Useful in 1v1, better in 2v2 | No pick may need a partner to be worth taking. Partner-only ideas are parked until 2v2 exists |
| Reward the state, not your own action | Setups apply a shared status to the target; payoffs check the status, whoever applied it. In 1v1 you set up and cash in yourself; in 2v2 your ally can too |
| Same tier = exclusive | One pick per tier, so the strongest pairs must share a tier. Pairs across tiers can be combined and need simulator checks |
| Short buffs and debuffs are fine | One-turn statuses are allowed, as long as they're rules rather than plain stat sticks |
| Gear last | Gear moves to level 9 so it can later become a separate mechanic without reshuffling the other slots |
| No stances | Considered (e.g. a Bodyguard stance that guards an ally every turn); rejected |

### Pick schedule

| Level | Slot | Pool |
|---|---|---|
| 3 | Technique I | Self rules (6) |
| 5 | Technique II | Setup/payoff rules (5) |
| 7 | Class Ability | The signature of any class unlocked this round |
| 9 | Gear | Unchanged (6) |

Even levels keep the dominion upgrades.

### Shared statuses

Each lasts one turn and applies to attacks from anyone.

| Status | Effect | Notes |
|---|---|---|
| Exposed | −25% defense; each application counts separately | Also from Defending twice in a row, Recover, and Hungering Moon while channeling |
| Marked | Takes +10 damage from attacks, for 2 turns | Was +5 for 1 turn |
| Staggered | −5 attack, for 2 turns | Was "can't Counter" for 1 turn; it took a choice away |

### Technique I (level 3)

| Technique | Rule | Source |
|---|---|---|
| Battle Fury | After a basic Attack, the next basic Attack deals +5 damage (not attack value); −3 defense while Furious; one stack | Discipline (was +4 attack / −2 defense per consecutive Attack, up to 3 stacks) |
| ~~Mana Shield~~ | Removed: +3 defense while Focused stalled 7–25% of 1v1 battles | Discipline |
| Restoration | Recover heals 80% now + 20% at the start of each of the next 2 turns; works on an ally | Discipline, kept (was 10% × 3) |
| Shadow Dance | Evading an attack grants Focus (only if not already Focused) | Discipline, kept |
| Backstab | +10 damage against a target that was Focused at the start of the turn | Discipline, kept (was +5) |
| Feint | +25 evasion on a turn you use any attack. No status; it applies to attacks resolving that same turn | Was a Combo (+10 evasion the turn after); +10 until the balance pass |

### Technique II (level 5)

| Technique | Setup → status | 1v1 line | 2v2 bonus |
|---|---|---|---|
| ~~Sunder~~ (removed) | Hitting a Defending target → **Exposed** | Hit through their Defend, then hit harder | Ally hits the Exposed target |
| Shield Bash | Being hit while Defending → attacker **Staggered** | Defend → Attack with no Counter risk | Ally attacks safely too |
| Riposte | Landing a Counter → attacker **Marked** | Counter → Attack | Both hit the Marked target |
| Spellbrand | Landing a Focused attack → target **Marked** | Focus → Attack → Attack | Ally follows up on the big hit |
| Second Wind | After Recover, +5 attack next turn; an ally you healed gets it too | Recover → Attack | Healing your ally also buffs them |

Spellbrand is new. Cut: Overpower (Sunder replaces it), Spellweave, Wellspring.

### Class Abilities (level 7)

Unlocked by reaching **class level 5** in that class this round. The class doesn't need to be active: `heroes.class_data` already keeps experience for every class played. Some signatures are passives; they still take this slot.

| Class | Signature | Kind |
|---|---|---|
| Alchemist | Volatile Mixture: 150% attack, 20% chance to hit yourself | Action |
| Architect | Fortify: +10 defense on you or an ally for 3 turns, not while Defending or Guarding | Action |
| Blacksmith | Forge: +4 attack for the rest of the battle, 2 uses (was +1, unlimited) | Action |
| Engineer | Caltrops: on you or an ally until struck; each attacker that turn takes 15 | Action |
| Scholar | Combat Analysis: target −4 defense for the rest of the battle, 2 uses (was −1, unlimited) | Action |
| Infiltrator | Shadow Strike: can't be evaded or Countered, 5 less damage | Action |
| Farmer | Hardiness: +5 defense at 40 health or less, not while Defending or Guarding (a class technique since the class technique pass; the old survive-at-1 is Cling to Life, enemies only) | Passive |
| Healer | Mending: while Focused, Recover and Heal add the focus value and keep the Focus (a class technique since the class technique pass) | Passive |
| Sorcerer | Channeling: a Focused basic Attack leaves its target Exposed (a class technique since the class technique pass) | Passive |
| Scion | None for now (Last Stand, +10% all combat stats at 40 health or less, was removed) | — |

Forge and Combat Analysis last the whole battle and stack. They now have 2 uses each, which caps what a turtling hero can build up (see Class abilities, second pass).

### Parked for 2v2

| Idea | Summary |
|---|---|
| Attunement | Focus can target an ally |
| Taunt | Enemies must attack you (the Provoked effect exists) |
| Fortify an ally | Built: Fortify targets you or an ally |

### First measurements (vs basic, 400 battles, seeds 101 / 5001)

| Pick | Score | Pick | Score |
|---|---|---|---|
| Battle Fury | 58.4 / 53.8% | Hardiness | 82.6 / 82.2% |
| Mana Shield | 55.1 / 55.2% | Last Stand | 72.2 / 74.8% |
| Restoration | 60.8 / 60.7% | Fortify | 71.2 / 72.0% |
| Shadow Dance | 55.1 / 55.9% | Shadow Strike | 55.2 / 58.1% |
| Backstab | 55.6 / 57.2% | Volatile Mixture | 53.0 / 54.6% |
| Feint | 51.7 / 56.6% | Forge | 52.0 / 51.6% |
| Sunder | 51.3 / 53.0% | Mending | 50.1 / 51.0% |
| Shield Bash | 50.8 / 48.6% | Channeling | 50.1 / 50.9% |
| Riposte | 54.0 / 52.2% | Tactical Awareness | 50.0 / 50.0% (never used) |
| Spellbrand | 52.9 / 51.9% | Combat Analysis | 50.0 / 50.0% (never used) |
| Second Wind | 58.9 / 58.1% | | |

The Tactician looks one turn ahead, and at first its setup value only counted its own attack and Focus, so it never picked Tactical Awareness or Combat Analysis and played blind to Exposed, Marked and Staggered. The setup value is now a damage edge against the opponent (see HERO_COMBAT.md); the affected picks were re-measured:

| Pick | Before | After damage-edge scoring |
|---|---|---|
| Battle Fury | 58.4 / 53.8% | 58.2 / 55.2% |
| Mana Shield | 55.1 / 55.2% | 53.5 / 55.2% |
| Sunder | 51.3 / 53.0% | 51.6 / 50.1% |
| Shield Bash | 50.8 / 48.6% | 50.7 / 52.5% |
| Riposte | 54.0 / 52.2% | 55.2 / 52.5% |
| Spellbrand | 52.9 / 51.9% | 53.0 / 51.4% |
| Forge | 52.0 / 51.6% | 51.6 / 51.4% |
| Tactical Awareness | 50.0 / 50.0% | 52.1 / 50.4% |
| Combat Analysis | 50.0 / 50.0% | 52.4 / 51.1% |
| Last Stand | 72.2 / 74.8% | 69.2 / 74.2% |

Seeing the statuses didn't lift the Technique II picks: they are weak in 1v1 on their values, not because the AI ignored them. The battle-long debuffs are now used but are worth little (−1 defense ≈ 5 points of edge against Focus's 10). Technique II setups are weak in 1v1 as expected for partner-oriented picks, but below the "useful in 1v1" rule. Hardiness, Last Stand and Fortify are far too strong.

### Simulator checks

| Check | Why |
|---|---|
| Every pick vs basic | Target 55–60% |
| Battle Fury + Riposte / Second Wind | Attack stacking across tiers |
| Backstab + Spellbrand | Both build on Focus |
| Shadow Dance + Spellbrand | Evasion grants free Focus, which feeds the Mark |
| Feint + Battle Fury | Both reward attacking every turn |
| 2v2 / 2v1 support | The simulator only runs 1v1; team value doesn't show up yet |

---

### Technique balance pass (built)

Measured in the Rust simulator (game rules, self-play, two seeds), in points above Greatsword in the same format (57.5% in both). Target: about gear level; useful in 1v1, can be better in 2v2.

| Technique | Change | 1v1 | 2v2 |
|---|---|---|---|
| Backstab | +5 → +10 | +2.6 (+9: +0.4) | +9: −5.6. Weak in 2v2: a Focused enemy is usually focus-fired anyway |
| Feint | +10 → +25 evasion, any attack | +0.8 | −0.8 |
| Second Wind | unchanged | 0.0 | +1.6 |
| Restoration | 10% × 3 → 20% × 2 | +1.5 | −1.3 |
| Shield Bash | Staggered: can't Counter → −5 attack for 2 turns | +2.1 | 0.0 (battles ~2.5 turns longer) |
| Battle Fury | 3 stacks of +4 attack / −2 defense → 1 stack of +5 basic-Attack damage / −3 defense | −1.5 (one seed; was +2.8) | +2.1 |
| Riposte / Spellbrand | Marked +5 for 1 turn → +10 for 2 turns | +2.1 / +2.0 | −1.9 / not run |

Other techniques:
- **Mana Shield**: removed. It stalled 7–25% of 1v1 battles (Focus then Defend).
- **Sunder**: removed. 1–3 turns of Exposed all measured −3 to −4, because opponents just stop Defending.
- **Shadow Dance**: −3.9 in 1v1. Reviewed and kept as is (rules tooltip added); it should improve as evasion gear spreads.

Simulator support is in `scripts/hero-combat-sim` (variants `fury`, `feint`, `backstab`, `secondwind`, `sunder`, `manashield`, `restoration`, `shadowdance`, `shieldbash`, `riposte`, `spellbrand`). None of these techniques are in `engine_fixtures.php` yet, so engine fidelity for them is unchecked.

## History: Disciplines (level 3)

Two warrior, two mage and two rogue picks. Measured after the redesign (400 battles per seed):

| Discipline | Flavor | Rule | vs basic (seeds 101 / 5001) |
|---|---|---|---|
| Overpower | warrior | Attacks against a Defending target ignore 5 defense | 51.0 / 54.5% |
| Battle Fury | warrior | +4 attack / −2 defense per consecutive Attack, up to 3 stacks; any other action resets it | 58.2 / 57.5% |
| Restoration | mage | Recover heals 80% now + 10% at the start of each of the next 3 turns; keeps the −5 defense penalty; recasting forfeits the remaining ticks | 59.6 / 58.0% |
| Mana Shield | mage | While Focused, +3 defense | 55.5 / 53.1% |
| Shadow Dance | rogue | Evading an attack grants Focus (only if not already Focused) | 54.9 / 58.1% |
| Backstab | rogue | +5 damage against a target that was Focused at the start of the turn | 57.8 / 57.4% |

How they got here:

| Discipline | First version | Problem | Change |
|---|---|---|---|
| Iron Guard (replaced by Overpower) | Defending reflected damage | Defend already nullifies most hits, so reflecting is free damage | Replaced with Overpower |
| Battle Fury | +5 / −5 per stack | Volatile (44–53% across seeds) | 2:1 ratio, +4 / −2 |
| Attunement | Focus stacks to 2 | No gain (50%): Focus's cooldown made a second stack take 3 turns | Focus also loses its cooldown |
| Rejuvenation | 60% + 60%, no defense penalty | 72%: skipping the −5 penalty is worth ~15 HP per battle | Penalty restored; 80% + 3×10% |
| Riposte | No Counter cooldown, counters at 70% | 28%, worse than nothing | Counters at full strength |
| Shadow Dance | Dodge (+40 evasion) instead of doubled defense | 47%: a coin-flip dodge loses to a reliable block | Keeps the doubling, adds +30 evasion and Focus on a dodge |
| Riposte | Counter has no cooldown | Removed the attacker's safe window after a Counter, so attacking was never safe (60–62%) | Cut. The name moved to the Counter Combo; Mana Shield took the slot |
| Attunement | Focus has no cooldown and stacks to 2 | Too fundamental a change to a basic action | Cut; Backstab took the slot. Focus no longer stacks for heroes |
| Shadow Dance | +30 evasion on Defend, zero-damage dodges, Focus on a dodge | Four effects in one pick; Defend already blocks unfocused hits, so the evasion mostly didn't matter | One rule: evading grants Focus. Rewards evasion builds, not Defend |
| Rejuvenation | — | Shares its name with a spell | Renamed Restoration (and Arcane Ward's idea became Mana Shield for the same reason) |
| Mana Shield | +4 defense while Focused | 56.1 / 55.0%, but longer battles (up to 21 turns): Focus is held until an attack spends it | Kept at +3 so holding Focus doesn't become a stall |

Cut ideas for this slot: Focus-breaking Counter ("Spoiling Strike": opponent's Focus is cancelled if you Counter it), a landed Counter refunding its cooldown (still removes the window), Counter striking setup turns (Counter would beat everything but Defend), reduced damage from focused attacks (weakens Focus further and makes both rogue picks defensive).

## History: Combos (level 7)

Being replaced by Technique II (see the redesign above). Every action opened at least one Combo (Defend opened two). Measured (400 battles per seed):

| Combo | After | Next turn | Sequence | vs basic (seeds 101 / 5001) |
|---|---|---|---|---|
| Feint | Attack | +10 evasion | Mostly passive; evasion is wasted if you Defend next | 57.1 / 57.0% |
| Shield Bash | Defend | +5 attack | Defend → Attack | 56.9 / 56.9% |
| Spellweave | Defend | +10 focus | Focus → Defend → Attack | 56.4 / 54.8% |
| Riposte | Counter | +5 attack | Counter → Attack | 62.1 / 58.2% |
| Second Wind | Recover | +5 attack | Recover → Attack | 58.1 / 59.4% |
| Wellspring | Focus | +5 recover | Focus → Recover (hold Focus while they Defend) | 50.4 / 51.4% |

Candidate values tried: Spellweave +5 focus 51.6 / 52.8%; Wellspring +10 recover 52.2 / 51.0%; Riposte +4 attack 54.6 / 54.5%. Spellweave was raised to +10 because it shared Defend with Shield Bash; head to head it scored 49.8 / 49.8% at +10 (48.1% at +5, seed 101).

Pairing rules used to choose the table, still useful for Technique II:

| Rule | Cuts |
|---|---|
| An action can't pay its own stat (Counter, Focus, Recover can't repeat; Attack→attack is Battle Fury's job) | all same-stat pairs |
| The stat must matter next turn | Attack → focus (attacking spends Focus) |
| No automatic bonuses | Focus → attack (you attack after Focus anyway) |
| No turtling | Counter → defense, Defend → counter/recover, Counter → recover, Recover → counter |
| No duplicate jobs | Attack → defense (Feint's job), Counter → focus (Ambush's job), Recover → focus (same line as Wellspring) |
| No defense payoffs: too easily telegraphed | Arcane Ward, old Second Wind (+5 defense) |
| Too easily avoided | Attack → recover |
| Counter payoffs | Spellweave's old +5 counter clashed with attacking after Focus; Attack → counter duplicated the Attack/Counter cycle Ambush (now Riposte) already rewards |

## Gear (moving from level 5 to level 9)

Two picks per archetype, with generic names; unique items are planned elsewhere. Gear may later become a separate mechanic from level upgrades, which is why it moves to the last slot. Scores are a level-9 hero against a basic level-9 hero (400 battles, seeds 101 and 5001).

| Gear | Archetype | Stats | Score |
|---|---|---|---|
| Greatsword | Warrior | +2 atk, −10 eva | 59.4% |
| Plate Armor | Warrior | +1 def, −3 eva (was −5) | 60.6% |
| Swordbreaker Dagger | Rogue | +6 counter | 59.2% |
| Hooded Cloak | Rogue | +10 eva (was +8) | 57.5% |
| Crystal Wand | Mage | +5 focus (was +6) | 58.7% |
| Vestments of Renewal | Mage | +3 recover (was +4 recover, +5 hp) | 61.3% |

Attack and defense are worth about 5% per point, because the base damage margin is only 40 attack vs 20 defense, so Greatsword pays for its attack with −10 evasion. Health is also strong: +5 hp alone moves Vestments about 6%, and +10 hp alone scored 68%.

| Tested and rejected | Score |
|---|---|
| Greatsword +3 atk, −5 eva | 69% |
| Greatsword +2 atk, −5 eva | 64% |
| Plate Armor +2 def, −5 eva | 71% |
| Swordbreaker Dagger +4 / +8 counter | 55% / 61% |
| Hooded Cloak +10 / +12 eva | 61% / 62% (one seed 65%) |
| Crystal Wand +4 / +8 focus | 52% / 61% |
| Vestments +4 recover only | 55% |
| Vestments +8 recover / +4 recover, +10 hp | 78% / 68% |

Re-measured in the Rust simulator under the current rules (1v1, two seeds, Greatsword 57.3 against a 57.5 baseline): Plate Armor +1 def / −5 eva 56.7; Swordbreaker 57.7; Hooded Cloak +8 eva 56.8, **+10 eva 57.6 (built)**; Crystal Wand +6 focus 59.0, **+5 focus 58.4 (built)**; Vestments +4 recover / +5 hp 63.2. Also tried: Plate +2 def / −10 eva 63.8; Plate +1 def, no penalty 59.6; Vestments +5 recover / −1 def 49.0; Vestments +4 recover only 59.0; **Plate +1 def / −3 eva 58.0 (built)**; **Vestments +3 recover only 58.5 (built)**. The scores in the table above are from the older in-game AI measurement. Defense is worth far more than attack per point, because Defend doubles it and battles run longer.

Cut from the old two-tier list: Executioner's Axe, Tower Shield, Dueling Blade, Leathers, Spellfocus Staff, Warded Robes. Shadowfang Daggers, Shadowweave Cloak and Archmage's Wand were renamed to the generic Swordbreaker Dagger, Hooded Cloak and Crystal Wand (Swordbreaker became counter only).

---

## Discussed, not built

| Idea | Summary | Status |
|---|---|---|
| Tactician as the in-game player AI | One strategy with balanced/aggressive/defensive as weight profiles, plus a temperature setting to make it beatable | **Parked.** Concern: it could play better than real players, making manual play pointless. Fix the information leak first (strategies can see the full `Battle`). |
| Team (2v2) PvP | Rules are built and measured (see Redesign: 2v2 Rules). Built as two players with templated allies (see Built); four-player 2v2 (partner visibility, pace, team matchmaking) remains | Two-player version built |
| Stances | Switchable modes, e.g. Bodyguard (always guard an ally, −attack, a turn to switch) | Rejected; only worth it if mechanically interesting |
| Insight meter | Fills on correct reads (Counter landing, Defend absorbing) and unlocks a signature move | Not chosen |
| Earned traits | The hero develops traits from how it fights over the round | Not chosen |
| Dominion-based bonuses | Small tilts from land, buildings or race | Not chosen (PvP fairness risk) |
| Damage behaviors | Crushing / Piercing / Lingering instead of magic vs physical | Not chosen |
| Owner reward for borrowed heroes | e.g. a raid score bonus | Dropped (side effects on tactic limits, realm wounds and rewards); nothing for now |

### 2v2 PvP issues identified

| Issue | Notes / options |
|---|---|
| Focus fire and snowballing | Both enemies hit one target; then it's 2v1. Measured and parked (see Snowballing above). Counterweights left to try: Riposte for allies, a downed state with revive |
| Counter's role | Wasted if nobody targets you, doubled under focus fire; fires through a guard |
| Heal stalling | Solved by 3 Recover charges per battle |
| Coordination | Teammates must see each other's queues; opponents must not |
| Pace | Four players' time banks; maybe a per-turn deadline |
| Automated partners | Need basic ally logic (heal the wounded ally, guard when hurt) |
| Matchmaking and ratings | Team formation, leaving, rating spread within a team |

### Tactician design (for reference)

| Part | Approach |
|---|---|
| Information | Public state only: health, stats, visible effects, cooldowns |
| Payoffs | Preview every pairing of both sides' legal actions on a copied state; evasion enumerated exactly |
| Solving | Exact zero-sum matrix solve (simplex). Regret matching was tried first but converged too slowly to be accurate. |
| Scoring | Damage dealt/taken, healing, change in attack potential, win/loss chance |
| Personality | Weight profiles plus a temperature setting toward uniform play |
| Strength | Beats today's `balanced` preset about 60–40 |

---

## Key decisions

| Topic | Decision |
|---|---|
| Content definitions | PHP classes, registered explicitly in `HeroCombatServiceProvider` |
| Health per level | 80 + 2 per level (level 10 = 100, the simulator's baseline). Was 60 + 5: one level's +5 health measured +5.9 points in 1v1, nearly a whole technique, so level gaps outweighed loadout choices |
| Turn model | Fully simultaneous: secret choices with per-player time banks, and damage and healing land together before anyone is defeated |
| Battle-wide rules | Combat rules apply to every kind of battle, PvE included. Bosses that need more healing than 3 charges get redesigned |
| Upgrade names | Warrior/mage/rogue flavor, no stored or shown "path". Names must not reuse spell names (`app/data/spells.yml`) |
| Combat categories | Techniques (passive rules, two tiers sharing one slot), Class Abilities and Gear; Disciplines and Combos are retired |
| Techniques | Bend a rule without removing a basic action's cooldown; useful in 1v1, better in 2v2 |
| Synergy | Shared one-turn statuses (Exposed, Marked, Staggered) that any attacker can cash in |
| Class Abilities | Available at class level 5 in any class played this round, not only the active one; no hero level requirement |
| Gear | Level 3 (second piece at 7), two different pieces; separate from upgrades so other sources can add more later. Balance numbers were measured at level 9 |
| Combat loadout | Definitions live in the combat registry, not `HeroHelper` or `heroes.yml`. Access uses the highest level in any class, so changing class never removes it. The loadout stays hidden from info ops. Changes apply to the next battle |
| Upgrade kinds in UI | Tooltips only |
| Party encounters | Only new encounters designed for two; no automatic scaling |
| Borrowing | Any realmmate, any number at once; pilot keeps full credit |
| Daily XP timing | Round day (`daysInRound`), not calendar day or rolling 24h |
| Migrations | The combat-engine migration is already applied and must not be edited; later columns (controllers, daily PvP reward) live in `2026_10_29_150132_add_controllers_and_pvp_rewards_to_heroes` |
| Branching | Build here, overwrite magic-system's unlock UI at merge time |

## Open follow-ups

| Item | Notes |
|---|---|
| Stale upgrade rows | Data sync never deactivates keys removed from `heroes.yml`; remove rows in a migration or keep retired upgrades in the file with `active: false` |
| Riposte, Second Wind values | As Combos they ran 58–62% at +5 attack; the Marked/ally versions need fresh numbers |
| Vestments of Renewal about 61% | Slightly above target; battles run longest (about 22 turns) |
| Technique II vs Technique I | They now compete for one slot, and Technique II picks scored lower in 1v1, so level 7 may not feel like an upgrade |
| Strong abilities at level 5 | Last Stand arrives at class level 5, earlier than the old level 7 (Fortify and Hardiness were reworked) |
| Combination testing | Technique + ability + gear builds, plus a fresh `--grid` run |
| Battles page Information | Remove what tooltips now cover; could become a full-width, in-depth explanation of how battles work, below the battle history |
| PvE pass after the 2v2 rules | Audit encounters for 3 charges, Exposed on repeat Defend, wasted overkill and enemy Defend or Recover loops; redesign any that need it |
| Boss spell timing | Magic Ward, Backlash and Silence still apply when they resolve, so id order can matter in PvE |
| Battle log wording | Turn summaries still say "Defend → Mage" and "Focus → Mage"; "(defending)" also covers Guard |
| Old replays | `hero-combat:replay` can't reproduce battles fought before simultaneous resolution, and battles in progress switched rules mid-fight |
| Loadouts in the simulator | Techniques, abilities and gear aren't modeled, so team value and the 2v2 rules with loadouts are unmeasured |
| First co-op encounter | Built: `ullyaoth` (World-Eater raid, Round 52), a required party of 2. See HERO_COMBAT.md |
| Release 2 | Turn on techniques and abilities (`heroes.loadout_slots`) after combination testing; decide when daily PvP experience returns |
| magic-system merge | Take this branch's slot UI and loadout; update their level-8 tests |
