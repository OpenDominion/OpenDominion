# Changelog
All notable changes relevant to players in this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/). This project uses its own versioning system.

## [Unreleased]

## [1.42.0] - 2024-10-14
### Added
- Offensive casualty statistics for the round are now displayed in the military advisor

### Changed
- Dark Elf: Delve into Shadow now enables Wizard Guilds to generate 0.05 Adepts per hour, exploration cost from Mastery removed
- Dark Elf Spellblade: defensive power increased to 3 (from 2.5)
- Spirit Ghost: offensive casualty reduction increased to 30%
- Spirit Spectre: offensive casualty reduction increased to 80% (from 70%)
- Undead: maximum population bonus reduced to 2.5% (from 5%), all units now require boats, boat capacity increased by 10, Death and Decay now turns 0.5% of your peasants into Zombies (from 1%) but cooldown reduced to 24 hours (from 48 hours)
- Vampire: Feast of Blood increased to 10% (from 9.5%)
- Vampire Bloodreaver: conversions increased to one per 20 sent (from one per 22)

### Fixed
- Mobile controls should now be black when using classic theme (dark mode)

## [1.41.3] - 2024-09-12
### Added
- Journal page: a place to keep your notes and calculations, dated by day/hour in the round
- Titles and Shadow League membership now visible on The World page

### Fixed
- Mastery bonuses now properly capped at 1000 mastery
- Bonus damage from Lightning Storm now queues for the remaining duration rather than the maximum duration

## [1.41.2] - 2024-08-20
### Fixed
- High Cleric's Tower no longer cancels Orc Bloodrage and Undead Zombie casualty penalties
- The 50% rule (severely outmatched) no longer includes draftee defense
- Starvation will now correctly kill 50/50 peasants/military
- 85%/95%/100% buttons on search page updated to only set minimum land

## [1.41.1] - 2024-08-13
### Changed
- King's Banner: now reduces invasion morale loss by 2% (from no loss)
- Gnome Juggernaut: base offensive power changed to 6.5, +0.5 vs 85% (from 6, +0.5 vs 75% or +1 vs 85%)
- Spirit Ghost: removed offensive casualty reduction (was -50%)
- Spirit Spectre: reduced offensive casualties to -70% (from -80%), 75%+ range only

## [1.41.0] - 2024-08-08
### Added
- New Hero Upgrades: select from a list of options as you level up
  - Divination (magic): -5% cost of info spells
  - Illusion (magic): Failed spy ops no longer reveal your identity
  - King's Banner (item): Invasion no longer reduces morale (75%+ range only)
  - Scribe's Journal (item): +10% research point gains from invasion
  - Spyglass (item): Survey Dominion and Land Spy now cost 1% spy strength
- New Advanced Hero Class: Scion
  - Provides both spy and wizard power at half the rate of the existing Infiltrator and Sorcerer classes
  - In order to unlock (via retiring your current hero), you must have at least 350 prestige. The Scion will then start with XP equal to your prestige at the time of selection. Cannot be selected until Day 10.
  - Unlocks the Disarmament upgrade: -100% offensive power, destroying mod buildings (Gryphon Nest, Guard Tower, Temple) awards discounted land credits
  - In addition, choose ONLY ONE of the following upgrades:
    - Revised Strategy: upon selection, reset all techs then gain RP to unlock up to 5 techs lost plus 50% of the remaining techs lost
    - Martyrdom: upon selection, reduces cost of spy/wizard training by 1% per 15 prestige (max 50% at 750 prestige) for 24 hours
- Search page now includes 85%, 95%, and 100% range buttons

### Changed
- Overpopulation now causes returning military to desert your army (when being invaded)
- Scaling modifier to 5:4 rule removed (was 1:1 after several invasions in a short timeframe)
- Reduce combat losses perk replaced with -50% casualties for defensive units (Goblin Shaman, Icekin Snow Witch, Orc Voodoo Magi), now unique to Dwarf Cleric
- Gnome: added +5% research point generation bonus
- Human Cavalry: -10p (from 1250p)
- Human Knight: +40p (from 1000p)
- Nomad: increased research point gains on invasion to +100% (from +50%)
- Nox: removed research point generation bonus (was +15%), added +5 boat capacity
- Spirit: Homeland changed to water (was swamp), mana production bonus removed (was +15%)
- Spirit Geist: -50% defensive casualties (was immortal)
- Spirit Phantom Knight: +75p, -5m, -50% casualties on offense (from 1025p, -50% casualties on defense)
- Spirit Spectral Warrior: -100p, -10m, -80% casualties on offense (from 1175p, immortal), +0.5 OP vs 95%
- Spirit: reverted all unit names (Phantom/Banshee/Ghost/Spectre)
- Troll Basher: now salvages 1 lumber from the battlefield on attack
- Troll Smasher: now salvages 1 ore from the battlefield on attack
- Undead Abomination: no longer needs boats, removed boat capacity bonus (was +5)
- Vampire: Feast of Blood now cancels all casualty reductions (was just immortality), converts increased to 9.5% (from 9%)
- Mastery loss halved, gains increased by up to 4 based on target's ratio
- Peasants protected from Fireball reduced to 5 per wizard (from 6), maximum protected reduced to 80% of max peasants (was 90% of max peasants)
- Wizard Guilds now increase protection from Fireball to 20 (was 24) for up to 6 wizards (was 5), max remains the same at 120 per building
- Spires now modifies Fireball vulnerability instead of damage
- Fireball damage is doubled between Shadow League members
- Bonus damage from Lightning Storm now lasts only until the effect expires

### Fixed
- The World page can no longer be accessed prior to round start

## [1.40.2] - 2024-07-11
### Fixed
- Spell damage modifiers from status effects are now multiplicative with other sources

## [1.40.1] - 2024-06-14
### Added
- Daily land and platinum bonuses can now be automated (doesn't count against daily action limit)

### Fixed
- Units with immortality now behave as expected

## [1.40.0] - 2024-06-07
### Added
- Notifications for changing realm roles
- Calculator page now shows black ops stats
- Rankings advisor now shows all titles/icons
- Title icons on realm page now link to their rankings pages
- The World page: lists all realms with their war status, wonders, and statistics

### Changed
- Recent invasions now reduce maximum offense:defense ratio (5:4 rule) by 0.125 per invasion after the fourth in a three day period (was second in a five day period), down to a minimum of 1 (was 0.75)
- Bots can now explore immediately (was prevented until hour 3) for up to 20 acres per hour (from 16)
- Fearless Adventures (-50% morale cost) replaced with Deep Pockets (+15% gains from theft)
- Exploring platinum cost tech perks are now halved for Firewalker/Goblin/Lycanthrope
- Urg Smash discount awarded for all buildings destroyed (from 25%), now excludes Nomad/Wood Elf
- Firewalker: explore penalty removed (was +5%)
- Firewalker: Alchemist Flame reduced to 12 platinum (from 15)
- Firewalker Salamander: casualty reduction increased to -50% (from -45%)
- Human Cavalry: +10p (from 1240p)
- Kobold Taskmaster: -25p (from 1375p)
- Orc: added +10% prestige gains
- Infamy removed
- Spy mastery now increases spy strength recovery by 0.2% (max 2%) and reduces spy losses by 5% (max 50%) per 100 mastery
- Wizard mastery now increases wizard strength recovery by 0.2% (max 2%) and reduces spell mana costs by 2% (max 20%) per 100 mastery
- Arcane Ward and Illuminate now increase chance of failure by 5% (was +10% defensive ratios)
- Damage reduction from SPA/WPA removed
- Sabotage Boats damage reduced to 2.3% (from 2.5%)
- Magic Snare damage reduced to 3.5% of current strength, min 1.5 (from 2 flat)
- Resilience decay increased to 20 (from 8)
- Peasant vulnerability to fireball set to 50% all round (was 35-45% by day)
- Wizards protect 6 peasants from fireball
- Wizard Guilds now produce 5 mana, increase the protection provided by 5 wizards to 24, and reduce lightning bolt damage by 10% per 1% owned up to a maximum of 50% at 5% owned (was spy and wizard cost/losses/recovery)
- Maximum lightning damage set to 20% of total investment (from 20-30% by day)
- Lightning bolt damage changed to 0.25% of current improvements (from 1% of vulnerable based on total investment)
- Lightning Storm: new status effect (spell) with 24 hour duration, increases lightning bolt damage by 50%, immune to Burning while active, applies Rejuvenation upon expiration, extended by 6 hours in mutual war
- Burning now increases fireball damage by 100% (was fixed pop growth) and grants immunity to Lightning Storm
- Burning and Lightning Storm are triggered by 20 successes of the matching spell (in a single hour or up to 30 over a 24 hour period), duration set to 20 hours +1 every 4 days from Day 4 to Day 44 (max 30)

### Fixed
- Spell reflect stats are now properly attributed to the dominion that cast the friendly spell

## [1.39.1] - 2024-04-01
### Added
- Town Crier can now be filtered by event types
- Active spells are now shown on the magic page
- Spells cast on other players are now shown on the Magic Advisor page

## [1.39.0] - 2024-03-15
### Added
- New Government Role: Spymaster
  - Can cancel any bounty
  - Can mark up to 10 dominions for observation
  - Dominions under observation provide bounty rewards for any stale info op
- Current temple percentage now shown on the military calculator
- Automated action to set draft rate

### Changed
- Icekin FrostMage: +15p (from 950p)
- Nomad Crossbowman: -15p (from 300p)
- Nomad Blademaster: +20p (from 980p)
- Orc Savage: -25r, +30 lumber
- Orc Voodoo Mage: -10p
- Orc Bone Breaker: -100r, +120 lumber, +1 OP per 300 prestige (from +1 OP per 333 prestige)
- Undead: Cull the Weak converts increased to 2 plus one per 750 acres (from 2 plus one per 1000 acres)
- Visionary Expansionist (tech): now grants +15% population growth (was +1 population from barren land)
- Visionary Expansionist swaps places with Urban Planner
- Minimum RP gained on attack reduced to 250 (from 750)
- Prestige formula changed to min(200x[RATIO]-115, 70) + max([LAND]-750, 0)/100 (from min(100x[RATIO]-40, 60) + [LAND]/150)
- Fireball vulnerability increased by 5% to 35-45% (from 30%-40%)
- Burning will now trigger after 10 spells in a 24 hour period (from a 10-20% random chance on each cast)
- Burning pop growth changed to 3% of vulnerable + 0.5% of maximum peasant population (from 6% of vulnerable)
-Assassins now retrain as spies on all failed operations at a rate of 50% (from 25% for black ops only)
- Arcane Ward and Illumination mana costs reduced to 1.5x (from 3x)
- Friendly spells now cost 3% wizard strength to cast (was 4%)
- Friendly spells are no longer prevented due to guard status (40%-250% still applies)
- Bounty rewards are now limited to 8 per day (was 12)

### Fixed
- The blank space in the header no longer links to the homepage
- Joining the realm Discord for the first time no longer throws an error
- Automations scheduled prior to round start will now run on the correct hour

## [1.38.4] - 2024-02-02
### Added
- The Bounty Board link in the sidebar will now show a blue indicator with the number of bounties you have posted

### Fixed
- Harbor will now apply the proper bonus to boat production
- Friendly spells will now show realmies who they were cast by
- Friendly spells will now properly display their current cooldown time on the magic page

## [1.38.3] - 2024-01-23
### Fixed
- Miner's Sight now properly cancels earthquake ore production penalty
- Automated actions are now sorted chronologically
- Spires and Harbor protection bonuses now properly displayed on op center pages

## [1.38.2] - 2024-01-18
### Fixed
- Burning regen now correctly based on max pop rather than current pop
- Bounties collected after the first 12 will now properly award double XP
- Added a slight delay between automated actions to reduce the chances of failure
- Daily actions will now properly reset when finishing protection

## [1.38.1] - 2024-01-09
### Fixed
- Hour change indicator will no appear when round is not active
- Units released in protection history will no longer increase the displayed draftee count
- Errors occurring on Hour 73 when importing a log will no longer result in a redirect loop

## [1.38.0] - 2024-01-04
### Added
- Automated Actions
  - You may now schedule up to 2 actions in advance, resets each day
  - Train, explore, construct, and cast spells (excluding Ares' Call and Fool's Gold)
- The Royal Court
  - The monarch can appoint members of the realm to positions of power
  - The General has the power to declare war
  - The Grand Magister and Court Mage can cast friendly spells
  - The Court Jester can change the realm name
- Friendly Spells
  - Beneficial duration spells that can only be cast on realmies, available to the Grand Magister and Court Mage of a realm or between Shadow League members in the same realm
  - Spell Reflect: 100% chance to reflect the next black op or war spell, 3 hour duration, 3 hour cooldown
  - Arcane Ward: +10% defensive wizard power, 6 hour duration, 3 hour cooldown
  - Illumination: +10% defensive spy power, 6 hour duration, 3 hour cooldown
- Bounty Board
  - You can now request info ops on a dominion from the Op Center
  - Other dominions in your realm earn a reward when they collect a bounty by gathering those ops
  - Bounties award double XP and the first 12 completed each day award 10 research points (no reward is earned for bounties on bots or for ops that have already been taken earlier in the hour)
  - Bounties expire after 12 hours
- Added checkboxes to exclude units entirely or just incoming units in the Calculators
- A new General Calculator has been added when directly navigating to the Calculators page
- At the hour change, a yellow border will appear around the screen

### Changed
- A portion of your peasants are safeguarded from being killed by fireballs, the percentage of peasants that are vulnerable to fireball starts at 30% of maximum on Day 4 and increases by 0.25% per day to a maximum of 40% vulnerable on Day 44
- Fireball: now kills 5% of your target's vulnerable peasant population (from 2.75% of current peasants), destroys 2% of your target's food (from 2.75%), 20% chance to apply the Burning status effect if at war
- A portion of your castle improvements are safeguarded from being destroyed by lightning bolts, the percentage of improvements that are vulnerable to lightning bolt starts at 30% of total investment on Day 4 and decreases by 0.25% per day to a maximum of 20% vulnerable on Day 44
- Lightning Bolt: now kills 1% of your target's vulnerable improvements (from 0.4% of current improvements), 10% chance to apply the Burning status effect if at war
- Burning: new status effect (spell) with 24 hour duration, unmodded population growth is fixed at 6% of your vulnerable peasant population, applies Rejuvenation upon expiration, extended by 6 hours in mutual war
- Rejuvenation: new status effect (spell) with 48 hour duration, increases population growth by 200%, reduces spell damage by 75%, immune to Burning, cancelled if target's realm declares war
- Up to 50% of your vulnerable peasant population and castle improvements are protected by defensive WPA (remains as damage reduction for all other ops), scaling changed to be more impactful early and less so as you approach the cap (formula in wiki)
- Spires: now protects up to 50% of your vulnerable peasant population and castle improvements (from up to 60% spell damage reduction), multiplicative with WPA protection
- Spires and Harbor: protection bonuses increase at 1.5x the rate of the primary bonuses (from 1.25x for Harbor), capped at +50%, and cannot be modified by masonries
- Energy Mirror: now reduces enemy spell damage by 15% and enemy spell duration by 2 hours (was 20% chance to reflect)
- Aquaponics (tech): now reduces duration of Burning by 4 hours (was -10% fireball damage)
- Wizard resilience removed
- Forest Havens removed
- Damage scaling by day in round removed from Disband Spies, Lightning Bolt, and Assassinate Wizards
- Fireball mana cost reduced to 3x (from 3.3x)
- Lightning Bolt mana cost reduced to 3.25x (from 3.5x)
- Disband Spies mana cost reduced to 3.5x (from 4.3x)
- Disband Spies damage increased to 2% (from 1.5%)
- Assassinate Wizards damage decreased to 1.5% (from 2%)
- Miasma duration increased to 8 hours (from 6)
- Success chances for ops are now modified by you and your target's current spy/wizard strength by 1% per 10% difference (ranging from -10% to +10%)
- Infamy and Mastery gains have been reduced by 2/3 (gains unchanged in mutual war and between Shadow League members)
- Application, wait, and leave times for the Shadow League reduced to 12 hours each (was 24/24/12, respectively)
- Offensive actions will be disabled anywhere between 15 and 9 hours remaining in the round (from 18 to 9 hours remaining)
- Factory: construction and rezoning costs reduced by 5% per 1% owned, up to a maximum of 50% at 10% owned (from 4% per 1%, max 60% at 15% owned)
- Factory: now employs 25 peasants (instead of 20)
- Goblin: gem investment bonus reduced to 10% (from 15%)
- Halfling: Frenzy now increases spy power by 20% and reduces spy losses by 10% (was -40% casualties), spy power racial bonus removed (was +25% spy power)
- Halfling Staff Master: casualties now reduced by 40%
- Kobold Underling: -5p (from 300p)
- Kobold Taskmaster: -25p (from 1400p)
- Kobold Overlord: -10p (from 1000p)
- Orc Savage: -15p (from 400p)
- Undead: Cull the Weak now kills up to 2 Skeletons and 2 Ghouls (plus 1 each per 1000 acres) every hour, re-queueing them as Necromancers and Death Knights, cancels and is cancelled by Midas Touch
- Undead Necromancer: now always convert Death Knights (from Ghouls when Cull the Weak was inactive)
- Visionary Expansionist (tech): now grants +1 population from barren land (was +15% population growth)
- Visionary Expansionist swaps places with Urban Planner

## [1.37.2] - 2023-10-24
### Added
- Protection History (on daily bonus page during protection) is now formatted to be imported as an Excel sim log

### Fixed
- Common wonders will now be taken out of rotation

## [1.37.1] - 2023-10-15
### Changed
- Dark Elf Swordsman: -15p (from 475p)
- Dwarf: ore production increased by 10% (was removed previously)

### Fixed
- Late starters will now receive bonus defense on the first day of the round
- Spy resilience bonus will now always be applied below 30% wizard strength

## [1.37.0] - 2023-09-23
### Added
- Exploration is now prohibited if a dominion has significantly less defense than a similarly sized bot
- Recent invasions now reduce maximum offense:defense ratio (5:4 rule) by 0.125 per invasion after the second in a five day period, down to a minimum of 0.75 (from 1.25)

### Changed
- Masonry castle bonus reduced to 2.6% per 1% owned (from 2.75% per 1% owned)
- Dwarf: ore investment bonus removed (was +25%)
- Dwarf Miner: ore production increased to 0.5 (from 0.4)
- Goblin Shaman: +5p (from 375p)
- Halfling: Frenzy casualty reduction increased to 40% (from 35%)
- Human Cavalry: -10p (from 1250p)
- Lycanthrope Garou: +15p (from 1100p)
- Lycanthrope Ratman: -15p (from 265p)
- Merfolk Merman: +10p (from 275p)
- Nomad Blademaster: -20p (from 1000p)
- Troll Basher/Smasher: -10p (from 1425p)
- Wood Elf Druid: OP/DP from forest is capped at +4.5 (from +5)
- Wood Elf Mystic: DP from forest is capped at +4.5 (from +5)

### Fixed
- Abandoned dominions will now have their vote for monarch revoked
- Attempting to send less than 40-60% of your target's defense will now properly cancel an invasion
- Dark Elf Swordsmen are no longer promoted on attacks against dominions under 75% of your size
- Frequently spawned wonders will now be excluded based on tiers rather than a smaller global list

## [1.36.0] - 2023-07-23
### Changed
- Packs with 5 players can no longer be paired together
- Bot defense formula (post-OOP) increased by 5%
- Lumberjack's Devotion (tech): barren population bonus removed (was +2), construction lumber cost bonus increased to -20% (from -10%)
- Military Culture (tech): prestige bonus increased to 7.5% (from 5%)
- Dwarf: ore invest bonus increased to +25% (was +15%), ore production bonus removed (was 10%)
- Dwarf Miner: ore production reduced to 0.4 (from 0.5)
- Dwarf Cleric: -15p (from 850p)
- Dwarf Warrior: -35p (from 1250p), -15r (from 100r)
- Icekin Ice Elemental: reduced casualties removed (was -10%)
- Lizardfolk Lizardman: +15p (from 1075p)
- Lycanthrope Ratman: +15p (from 250p)
- Merfolk: +2.5% explore cost removed, -10% gem production added, offensive power increased to 7.5% (from 5%)
- Orc: prestige bonus removed (was +7.5%)
- Orc Bone Breaker: offense changed to 4 + 1 per 333 prestige, max 3 (from 4 + 1 per 250 prestige,  max 3)
- Orc Savage: now 4/0 400p 25r (from 3.5/0 330p 20r)
- Spirit Phantom Knight: +25p (from 1000p)
- Spirit Spectral Warrior: +25p (from 1150p)
- Undead: mana production increased to +20% (from +10%)
- Undead Cull the Weak (spell): peasants killed reduced to 1% (was 10%), mana cost reverted to 5x (was 3x)
- Undead Raise the Dead (spell): removed
- Wood Elf: offensive power reduced to +2.5% (from +5%)

## [1.35.1] - 2023-06-15
### Fixed
- Target Land box has been added back to OP calculator (Gnome)
- XP gains from ops are no longer being rounded (Shrines, Shadow League)
- Erosion and Verdant Bloom now award the correct amount of XP
- Shadow League membership can now be seen by realmies who aren't members
- Government page now properly shows expiring wars as active, no longer renders an unnecessary scrollbar

## [1.35.0] - 2023-05-25
### Changed
- Discounted construction from invasion now scales up by 1% per day from Day 20 to Day 40 (was 2% per day from Day 25 to Day 35)
- Base dock protection increased to 2.25 (from 2), scaling unchanged
- Increased temporary portion of Lightning Bolt damage to 10% base (from 0% base), conditional 25% from wizard resilience still applies
- Cyclone now awards wizard mastery when rebuilding a wonder at the same rate as prestige, 25-75 based on damage dealt
- Dark Elf Swordsman: -10p, Ascendence now a unit perk
- Dark Elf: new spell Delve into Shadow reduces explore cost by 1% per 100 Wizard Mastery and failed spells refund 50% of their mana cost
- Goblin: gem investment bonus reduced to 15% (from 20%)
- Icekin Ice Elemental: added -10% casualties on offense
- Kobold reworked
- Orc: prestige bonus increased to 7.5% (from 5%)
- Undead reworked

### Fixed
- Invasions below 75% no longer cause XP loss

## [1.34.1] - 2023-04-09
### Fixed
- Defensive casualty reduction bonuses are now properly applied when the recently invaded reduction is also active, but reduction from all sources is capped at 25% of base (0.9% vs 3.6%)

## [1.34.0] - 2023-03-09
### Added
- New Wonder: Altar of Heroes
  - Hero bonuses increased by 100%
  - +5% hero experience gains
- New Wonder: Wayfarer's Outpost
  - +2% platinum production
  - -5% exploring platinum cost
- New Valhalla Rankings: Largest and Strongest solo players

### Changed
- Prestige formula changed to 100x[RATIO] - 40 + [LAND]/150 (from 120x[RATIO] - 50 + [LAND]/250)
- Tech cost changed to 2.5x[LAND] + 100x[TECHS] (from 2.5x[LAND] + 150x[TECHS])
- Wizard resilience is again gained immediately, 10 per successful fireball or lightning bolt
- Wizard resilience now increases peasant change by [RESIL/125]% of your population deficit
- Fireball damage increased to 2.75% (from 2.5%)
- Lightning bolt repair invest bonus replaced with temporary damage portion based on wizard resilience, [RESIL/40]% of damage is automatically repaired in 12 hours
- Black op spells with duration changed to 8 hours, +2 in war, +4 in mutual war (from 6, +3, +6)
- Shadow League: cannot leave for 24 hours after joining (down from 48)
- 25% of assassins lost to black ops will be placed in the training queue as spies
- Nox: research point generation bonus increased to 15% (from 10%)
- Nox: Nightfall reduced to +5% offense (from +7.5%)
- Goblin Wolf Rider: +25p (from 1275p), +10r (from 100r)
- Goblin Hobgoblin: -25p (from 1050p)
- Halfling: Frenzy casualty reduction increased to 35% (from 30%)
- Firewalker Salamander: casualty reduction increased to 45% (from 40%)
- Orc: gains 5% additional prestige from invasions
- Wood Elf Longbowman: -20p (from 380p)
- Land spy now shows percentages with incoming land instead of constructed

## [1.33.0] - 2023-01-13
### Added
- Automated protection via importing from an Excel Log

### Changed
- Minimum war duration changed to 48 hours (from 72), however the invasion bonuses will remain active for 12 hours after war is cancelled
- Maximum war duration changed to 108 hours (from 120)
- Docks now protect 2 + [0.05 x daysInRound] boats (from 2.5 + 0.05 x [daysInRound - 25] after Day 25)
- Fireball damage reduced to 2.5% (from 2.75%)
- Wizard resilience now takes 12 hours to go into effect
- Wizard resilience now grants additional fireball damage reduction of [RESIL/1.25]% (cap from all sources remains at 80%)
- Wizard resilience now increases population growth by [RESIL/100]% of your current peasants (was percentage of maximum peasants)
- Spires improvement no longer increases defensive wizard power, only offensive
- XP gained from info ops while in Shadow League increased to 1.5 (from 1)
- Dark Elf Adept: changed to .2 wizard on defense (was .25)
- Dark Elf Swordsman: +25p (from 460p)
- Kobold: removed -10% spy and wizard power penalties
- Kobold Beast: -10p (from 960p)
- Lizardfolk Lizardman: +25p (from 1050p)
- Merfolk: explore cost penalty reduced to +2.5% (was +5%)
- Nomad: +50% research point gains from invasion
- Nox: Nightfall increased to 7.5% offense (from 5%)
- Nox Nightshade: changed to 0/2 +1 DP for every 10% swamps (from 0/3 +1 DP for every 12% swamps)
- Sylvan Dryad: changed to .2 wizard on offense (was .25)
- Undead Progeny: -25p (from 860p)
- Undead Vampire: converts up to one Progeny for every 18 sent on attack (from 20)
- Tech Adjustments
  - Architect's Flourish increased to -10% construction platinum cost (from -7.5%)
  - Carpenter's Knowhow increased to -5% construction platinum cost (from -2.5%)
  - Dark Pact increased to 10% mana production (from 7.5%)
  - Explorer's Instinct increased to -5% construction platinum cost (from -2.5%)
  - Lumberjack's Devotion now grants +2 population from barren land
  - Menace increased to +2 mana and +1.5 mana per war (max +5) from +1 mana and +2 per mana war (max +4)
  - Midas's Bargain reduced to 2% platinum production (from 2.5%)
  - Midas's Fountain reduced to 4% platinum production (from 5%)
  - Miser's Grasp now grants +10% defensive spy power
  - Planar Emmissaries reduced to 5% mana production (from 7.5%)
  - Prefabrication increased to -10% construction cost (from -5%)
  - Urg Smash Technique now grants discounted construction equal to 25% of acres destroyed (was platinum refund equal to 7.5% of current construction cost)
  - WaveHack's Expertise increased to -10% construction lumber cost (from -5%)

### Fixed
- Amplify magic tooltip adjusted
- Hero class now shown in Valhalla
- Posting in the round forum will now take you to the most recent page

## [1.32.1] - 2022-11-14
### Changed
- Black ops and Theft success rates now use the same formula (an approximate average of the two)
- Adjust XP gain for invasion to 1 per acre gained (from 1 per acre captured)
- Adjust XP gain for info/black/war to 2/4/6 (from 1/3/5)
- Heroes now lose 1 XP per acre lost from invasion
- Max Hero level increased to 12 (from 10)
- Adjusted XP requirements for all levels
- Alchemist bonus reduced to 0.2% per level (from 0.4%)
- Human Cavalry: +50p (from 1200p)

## [1.32.0] - 2022-11-01
### Added
- Heroes: create a hero for your dominion
  - Select a class which determines the hero's passive bonus
  - Gains experience by attacking and performing info/black ops
  - Level up and increase your hero bonus
  - Can be retired and half its experience will be applied to a new one
- New Spell: Disclosure
  - Info op for revealing inforamation about heroes
- New Unit: Assassins
  - Costs 1000p and 1 spy to train, counts as 2 spies
  - Cannot be killed by Disband Spies
- Production Advisor now shows various expenditure bonuses
- Settings page where you select your preferred title for the round forum, choose to display it on the realm page, and toggle Shadow League visibility

### Changed
- Shrines:
  - Casualty reduction removed (in lieu of Healer hero)
  - Increases hero experience gain by 2% per 1% owned, up to a maximum of 20% at 10% owned
  - Increases hero bonus by 50% per 1% owned, up to a maximum of 500% at 10% owned
- Barracks:
  - No longer scale with prestige
  - Increased to 36 housing (from 35)
  - Bunk Beds tech increased to +2 barracks housing (from +1)
- Halfling:
  - Defensive Frenzy removed
  - added +10% defensive power racial
  - removed 30% fewer casualties from Staff Master
  - Frenzy: new spell, 30% fewer casualties
- Icekin:
  - Blizzard removed
  - added +5% defensive power racial
  - removed 5% platinum production racial
  - Alchemist Frost: new spell, 15% platinum production (does not stack with Midas)
- Resilience:
  - Spy resilience is now only increased by Magic Snare
  - Wizard strength regen under 30% is now increased by by 1% per 100 spy resilience (from 1% per 250)
  - Wizard resilience is now only increased by Fireball
  - Peasant change due to wizard resilience is now increased by 10% of your total pop growth (Plague, Harmony, temples, techs, etc)
- Lightning repair bonus now scales based on total damage received, up to 100% at 10% damage received (from constant 100%)
- Spy/Wizard ratio required for maximum damage reduction from black ops now scales up during the round: 0.5 at Day 4, 1.0 at Day 24, 1.5 at Day 44 (from 1.0 all round)
- Research point gains on attack are now 5x land conquered OR 750 whichever is higher (from 750 flat)

## [1.31.0] - 2022-09-02
### Added
- Black ops damage taken is now visible in the statistics advisor

### Changed
- Towers castle improvement renamed Spires
- Resilience no longer reduces damage from black ops
- Spy/Wizard ratio now reduces damage from black ops by [RATIO/2]% (max 50% at 1.0)
- Spy Resilience now increases wizard strength recovery by [RESIL/250]% (max of 4% at 1000)
- Wizard Resilience now increases minimum peasant change to [RESIL/250]% of maximum peasants (max 4% of peasants at 1000)
- Fireball damage increased to 2.75% (from 2.65%)
- Sabotage damage increased to 2.5% (from 2.4%)
- Magic Snare damage changed to 2% (from 3.5% of current strength)
- Wizard Guilds renamed to Guilds, loses mana production / castle protection, gains spy bonuses
  - Spy and Wizard Strength refresh rate increased by 0.1% per 1% owned, up to a maximum of 1% at 10% owned
  - Spy, Wizard, and Archmage training costs reduced by 3.5% per 1% owned, up to a maximum of 35% at 10% owned
  - Losses on failed black ops reduced by 2.5% per 1% owned, up to a maximum of 25% at 10% owned
- Forest Havens lose spy bonuses
  - Produces 25 lumber per hour
  - Fireball damage reduced by 10% per 1% owned, up to a maximum of 80% at 8% owned
  - Disband Spies and Assassinate Wizards damage reduced by 10% per 1% owned, up to a maximum of 50% at 5% owned
- Cyclone cost reduced to 2x (from 3x)
- Trick of the Light tech now protects mana instead of decreasing mana cost
- Castle improvements destroyed by Lightning Bolt can now be 'repaired' (investment is doubled)
- Reinstated prestige penalty for invasions against bots (-5% per hit after the 4th, max -50%)
- Dark Elf Spellblade: +0.5 DP (from 2)
- Dark Elf Swordsman: +35p (from 425p)
- Firewalker Phoenix: no longer dies to Icekin
- Firewalker Salamander: +25p (from 925p)
- Goblin: Gem investment bonus increased to 20% (from 15%)
- Goblin Wolf Rider: -30r (from 130r)
- Human: Crusade no longer kills immortal units
- Nomad Crossbowman: +25p (from 275p)
- Nomad: Favorable Terrain changed to 1% offense per 1% barren, max 10% at 10% barren (from 1% per 1.5% barren, max 10% at 15% barren)
- Spirit: Maximum population bonus removed (was 2.5%)

### Fixed
- National Bank page will now correctly display Banker's Friend tech
- Copy Ops no longer shows spell caster
- Dominions in protection or abandoned can no longer access op center
- Dominions in the Graveyard will be assigned a new realm when they perform an action
- New realms will no longer be created for packs who join late

## [1.30.1] - 2022-07-02
### Changed
- Minimum defense cannot go below 750
- School production capped at 50% of current land
- Resilience formula adjusted to scale more quickly
- Resilience gains for successful ops increased to 10 for spy and 12 for wizard (from 8 for spy, 11 for wizard)
- Resilience now reduces the effectiveness of hostile duration spells (Plague/Swarm/etc)
- Magic Snare minimum damage increased to 1.5% before resilience (from 1% after resilience)
- Lightning Bolt, Disband Spies, and Assassinate Wizards damage will now scale linearly based on day of the round (max 137.5% at Day 10, 100% at Day 25, min 62.5% at Day 40)
- Shadow League: double infamy bonus removed, 75% of spy/wizard losses from failed operations will now be placed into the training queue instead of destroyed
- Amplify Magic now increases mana cost by 200% and duration by 150% (from 150% for both)
- Firewalker Salamander: casualty reduction reduced to 40% (from 50%)
- Icekin FrostMage: +50p (from 900p)
- Nomad: Favorable Terrain changed to 1% offense per 1.5% barren, max 10% at 15% barren (from 1% per 1% barren, max 10% at 10% barren)
- Spirit Phantom Knight: removed -50% casualties on offense

## [1.30.0] - 2022-06-20
### Changed
- Bots have had their OOP defense decreased below 525 acres and greatly increased above 525 acres
- Minimum defense changed to 10x Land - 3200 (from 3x Land)
- Repeatedly invading bots no longer incurs a prestige penalty
- Tech cost changed to 2.5x Land + 150x Techs, min 3750 (from 3600 + 0.65x Land + 150x Techs, min 3900)
- Land lost on invasion reduced to 75% of classic (from 80%), ratio of land generated to land lost remains at 1:1
- Discounted construction from invasion now scales up by 2% per day after day 25 (max 70%)
- Fool's Gold cost reduced to 1x Land (from 5x Land)
- New Spell: Amplify Magic - increases the mana cost and duration of your next non-cooldown self spell by 150%
- Dark Elf Adept: -50p (from 1200p)
- Dark Elf Spellblade: now plunders mana
- Gnome Juggernaut: -25p (from 775p)
- Human: Crusade changed to +10% offense (from +5%)
- Lizardfolk Lizardman: -35p (from 1085p)
- Lycanthrope Werewolf: offensive casualty reduction removed (was -25%)
- Orc Bonebreaker: +1 DP (from 2)
- Spirit Phantom Knight: added -50% casualties on offense (was -50% on defense only)

### Fixed
- Mastery can now be gained while Infamy is capped

## [1.9.0] - 2022-04-20
### Changed
- Maximum pack size increased to 5 (from 4)
- Packs with fewer than 5 players may select two of each race
- Time between realm assignment and round start increased to 72 hours (from 48)
- Base prestige gain slightly increased
- Bot DP reduced at lower land sizes
- Prestige penalty for hitting bots reduced to 5% each (from 10% each) after the 4th
- Tech cost now increased by 150 for each unlock (from 100)
- Discounted land will again be awarded when buildings are lost to invasion
- Infamy now increases mana production at the same rate as gems/lumber/ore
- Infamy gain now based on chance of success instead of relative ratio
- Hourly infamy decay reduced by 25% while in the Shadow League
- Spies and wizards lost due to failed black ops when targeting players with much higher ratios have been capped around 2 SPA/WPA (info ops already capped when over 0.6 SPA)
- Magic snare now does a minimum of 1% damage after resilience (from 1.5% before resilience)
- Wizard strength refresh rate increased by 1% if you would remain under 30% the following tick
- Smithy: maximum cost reduction increased to 36% at 18% owned (from 30% at 15% owned)
- Halls of Knowledge: now generates 15 research points per hour and increases school production by 10% (from 60 RP per hour)
- Goblin Wolf Rider: -25p (from 1300p)
- Gnome Juggernaut: +50p (from 725p)
- Halfling Master Thief: changed to .25 spy on defense (from .333) effectively .3125 with racial bonus
- Halfling Staff Master: casualty reduction reduced to 30% (from 40%)
- Human Cavalry: -50p (from 1250p)
- Lizardfolk Chameleon: changed to .25 spy on offense (from .2) effectively .275 with racial bonus

### Added
- Quick Start builds for teching and improvements
- Max button for training troops
- Max button for releasing draftees
- Prestige loss now visible in friendly battle reports
- Last online tooltip for players who share advisors
- Button links to targeted actions from ops center
- Additional channels for realm Discord servers

### Fixed
- Infamy and Resilience now properly cap at 1000
- Made some sidebar links more visible as buttons

## [1.8.3] - 2022-03-02
### Fixed
- Realms with 6 packed players or less will now be properly balanced during realm assignment
- Increase farms/towers for some NPDs
- Can no longer apply for guards prior to round start
- Hide username on government page when sharing disabled
- Inactive spells removed from calculators (Warsong)
- Great Flood was mistakenly set to 50% instead of 25%
- Shadow League membership now visible on search page

## [1.8.2] - 2022-02-19
### Changed
- Shadow League application requires 24 hours (up from 12)
- Shadow League membership is now visible to everyone
- Info ops for Shadow League members now use only 1% strength
- Losses for failed ops between Shadow League members are halved
- Infamy bonuses increased to a maximum of 10% and 4% (from 7.5% and 3%)
- Assassinate draftees and duration spells now award infamy during war (modified by hours applied out of max duration)
- Necromantic Ritual changed to kill 2% of target's peasants and converts 5% of the dead into Progeny (from 1% of wizards and 100% converted)
- Minimum tech cost slightly reduced to 3900 at 461a (from 4000 at 615a)
- RP gain on attack increased to 750 (from 500)
- RP gain from daily platinum bonus increased to 350 (from 250)
- Minimum wonder health when rebuilt from neutral increased to 250k for Tier 1 and 200k for Tier 2 (from 150k)
- Wonder buffs:
  - Fountain of Youth: max population to 3% (from 2.5%)
  - Golden Throne: prestige gains increased to 25% (from 15%)
  - Gnomish Mining Machine: ore production to 20% (from 15%)
  - Hanging Gardens: food production to 25% (from 20%)
  - High Cleric's Tower: ignore ALL casualty reductions (was immortality only)
  - Ruby Monolith: casualty reduction increased to 10% (from 7.5%)
  - School of War: barracks housing increased to +3 (from +2)
- Quick Starts have been updated

## [1.8.1] - 2022-02-07
### Changed
- Time until round start now shows both days and hours remaining
- Bot defense reduced by ~6% at spawn

### Added
- Status of info ops now shown when calculating an out-of-realm dominion
- Calculator results now show current land total

### Fixed
- Revelation/Vision archives no longer throw 500
- Missing notification descriptions for new spells
- Issues with shared advisors
- Issues with bot AI
- Bots will no longer complete any military training prior to round start
- Auto-select dominion for a round that hasn't started yet
- Favorable Terrain, Erosion, Verdant Bloom now working correctly

## [1.8.0] - 2022-02-01
### Changed
- Protection now ends at the start of Day 1 (OOP = round start)
  - Realms assigned 48 hours prior to round start
  - War/Guards enabled Day 3
  - Black ops enabled Day 4
- Spell system rebuilt
- Advisors are now shared by default when assigned to a realm
  - Dominions that join after realm assignment will not get access
- Platinum production bonuses no longer capped at 50%
- Bot DP increased at lower land sizes
- Bot spy/wizard ratios are now randomized and slowly increase until Day 35
- Bots will join elite guard randomly between 2000 and 3000 acres
- 33% Rule increased to 40% (must keep 40% of your total defense home when invading)
- Invasions that would be overwhelmed by 50-80% (randomized) or more will fail
- Black op spells with duration changed to 6 hours, +3 in war, +6 in mutual war
- Wonder HP reduced to 150k/75k (from 250k/150k)
- Cyclone deals double damage against neutral wonders
- High Clerics Tower and Onyx Mausoleum removed
- Halls of Knowledge now produces 60 RP/hr (from 100)
- School of War returns (+2 barracks housing)
- Maximum prestige gain from rebuilding another realm's wonder reduced by 25 (from 100)
- Prestige gain formula changed to 115x LandRatio - 50 + TargetLand/200 (from 100x LandRatio - 40 + TargetLand/250)
- Tech cost changed to 3600 + 0.65x Land + 100x Techs (from 9500 + 100x Techs)
- Platinum bonus research points reduced to 250 (from 750)
- Research points gained on invasion reduced to 500 (from 1000)
- Research production penalty from invasion replaced with flat 500 loss from queue when invaded
- School: research points produced changed to SCHOOLS x (1 - SCHOOLS/LAND) down to a minimum of 0.5 per school at a maximum of 50% owned (from 25 per 1% owned up to a maximum of 750), research points gained on invasion removed (from 125 per 1% owned up to a maximum of 2500)
- Smithy: maximum cost reduction reduced to 30% at 15% owned (from 36% at 18% owned)
- Masonry: lightning bolt protection removed (was 1% per 1% owned up to a maximum of 10%)
- Wizard Guild: lightning bolt protection added, 6% per 1% owned up to a maximum of 60% at 10% owned
- Gryphon Nest: OP bonus increased to 1.75x per 1% owned up to a maxium of 35% at 20% owned (from 1.6x per 1%, max 32% at 20% owned)
- Guard Tower: DP bonus increased to 1.75x per 1% owned up to a maxium of 35% at 20% owned (from 1.6x per 1%, max 32% at 20% owned)
- Temple: DP reduction increased to 1.5x per 1% owned up to a maximum of 25% at 16.7% owned (from 1.35x per 1% owned, max 25% at 18.5% owned)
- Dark Elf Spellblade: -50p (from 1250p), -25r (from 75r)
- Human Knight: -25p (from 1025p)
- Nomad Blademaster: -15r (from 40r)
- Nomad Horse Archer: -40r (from 80r)
- Nox Fiend: -4m (from 12m)
- Nox Lich: +20p (from 950p)
- Nox: new spell Miasma target's wizard power reduced by 5% and mana decay increased by 50% for 6 hours
- Spirit Phantom Knight: -50p (from 1050p)
- Spirit Spectral Warrior: -50p (from 1200p)
- Undead Skeleton: renamed Dire Bat, no longer needs boats
- Undead: new spell Necromantic Ritual kills 1% of target's wizards and converts them into Progeny after 12 hours (added to invasion queue)

### Added
- New Guard: Shadow League
  - Enables war-only black ops between members
  - Production bonuses from Infamy are doubled
  - Only visible to other members
  - 12 hour delay to join and leave, cannot leave for 48 hours after joining, black ops without war reset leave timer
- Ability to search users in Valhalla
- Aggregated league stats per user in Valhalla
- Lifetime standings per league in Valhalla
- Setting to opt out of auto-sharing realm advisors
- Setting to opt out of sharing username alongside advisors

### Fixed
- Gnome and Icekin bots spawn with more ore mines
- Offense calculator now defaults to max land ratio (Gnome)
- Invasions below 75% no longer count toward future prestige penalties

## [1.7.2] - 2022-01-16
### Added
- Land loss/gain now visible when selecting a target for invasion

### Fixed
- Onyx Mausoleum now properly increases casualties
- Spy/Wizard rankings now show properly in valhalla
- Maintenance page now says 'maintenance' and not 'server error'

## [1.7.1] - 2021-12-14
### Added
- Bot status can now be filtered on the Search page
- Late starts (Day 5 or later) are now required to train adequate defense while the quick start options will begin with defense already trained

### Fixed
- Tech percentage no longer based on flat 10k cost
- Notification settings will now work properly when unchecked

## [1.7.0] - 2021-12-03
### Changed
- Quickstarts for all races have been redesigned in order to be easier to update
- Exploration costs reduced, formula coefficients changed to 0.6 (from 0.61) and 1.299 (from 1.305)
- Tech cost changed to base 9500 research points, +100 for each tech unlocked
- Hourly RP production capped at current land total
- Schools now generate an additional 125 RPs (down from 130) per 1% owned when invading targets 75%+ your size, up to a maximum of 2500 at 20% owned and capped at 5x current land total
- Raze casualties (unsuccessful attacks) will now scale linearly from zero casualties at 80% of OP:DP up to full casualties when successful
- Prestige gain on bots will now be reduced by 10% per hit after the 4th attack (up from 5%)
- Prestige gain formula adjusted to 100 x min(1, LandRatio) - 40 + TargetLand/250 (from 60 x LandRatio)
- Defensive casualties will now use either total reduction for the dominion OR the penalty from recent invasions NOT both
- Halls of Knowledge now produces 100 RP/hr (from 15% production bonus) and spawns with 150k HP (from 250k)
- Dwarf Cleric: no longer kills immortal units
- Icekin Frostmage: +50p (from 850)
- Kobold: population growth bonus increased to 20% (from 10%)
- Lycanthrope Scavenger: +25p (from 325)
- Lycanthrope Garou: now converts up to one for every 15 sent on attack (from 12)
- Lycanthrope Feral Hunger: now converts up to one for every 15 sent on attack (from 25)
- Nomad: 10% construction cost penalty removed
- Nomad Favorable Terrain: now grants +1% OP for every 1% barren land (from 2% barren land), max unchanged at +10%
- Orc Savage: offense reduced to 3.5 (from 4), costs 330p, 20r (from 375p, 25r)
- Spirit: maximum population increased to 2.5% (from 0%)
- Wood Elf: rezone costs down to +10% (from +25%)

### Added
- Current Icekin WPA visible on clear sight and in calculator
- Additional Discord info/links
- Player names are now visible on the realm page after the round has ended
- Player names now link to their valhalla page

### Fixed
- War declarations against a realm will now appear in their realm's town crier

## [1.6.0] - 2021-10-12
### Changed
- Construction costs reduced across the board by up to 20% and further reduced based on your conquered land total
- Destroyed buildings during invasion no longer grant the target discounted construction
- Prestige gain is now based only on relative land size, target prestige and recent invasions are ignored
- Land loss decreased to 80% of the DC formula (from 90%), generated land now equal to 100% land lost (from 66.67% of land lost)
- Base defensive casualties reduced to 3.6% (from 4.05%), modifier for recent invasions reduced to 100%/75%/50%/25% (from 100%/80%/60%/55%/45%/35%)
- Conversions are now based only on units sent and relative land size, defensive casualties are ignored
- Dock food production increased to 40 (from 35)
- Forest Haven lumber production increased to 25 (from 20)
- Wizard Guilds now produce 15 mana (reduced spell cost bonus removed)
- Failed war operations increase the target's resilience by 2
- Dark Elf reworked
- Dwarf Cleric: increased DP to 4.5 (from 4), +90p (from 760)
- Nomad reworked
- Lycanthrope: homeland reverted to caverns (from forest), new spell Feral Hunger allows werewolves to convert more werewolves
- Merfolk: food production decreased to 5% (from 15%), explore cost increased by 5%
- Orc Bone Breaker: 4/2, +1 OP for every 250 prestige (from 4/3, +1 OP for every 375 prestige)
- Spirit reworked
- Undead: maximum population increased to 15% (from 12.5%)
- Undead: Parasitic Hunger always increases conversions by 20% and spreads plague
- Wood Elf: rezone cost increased by 25%
- Attacking success and defending failure stats are now only recorded for 75%+ invasions
- Bots will now grow faster, defense formula adjusted
- Reduced prestige gain for each attack against a specific target or any of the bots when invading them repeatedly over the course of the round

### Fixed
- Revelations cast at hour change no longer have a chance to cause 500 errors
- Dark Elf and Spirit quick starts now have the correct amount of docks and alchemies

## [1.5.0] - 2021-07-25
### Changed
- Barracks now house 35 trained or in training military units, modified by prestige (from 36 unmodified)
- Bunk Beds tech reduced to +1 barracks housing (from +2)
- School of War has been removed
- New Wonder: Astral Panopticon grants surreal perception
- Schools now generate 25 Research Points per 1% owned up to a maximum of 750 at 30% owned (down from 26 per 1%, max 1040 at 40%)
- Wars will now automatically expire after 5 days
- Black ops success rate reduced when target's spy/wizard ratio is high
- Fireball mana cost reverted to 3.3x (from 3x)
- All NPDs will now actively train troops, cast spells, etc
- NPDs will now start with a flat amount of defense incoming instead of a percentage of current
- NPDs now lose 25 prestige when invaded
- Dark Elf Adept: +50p (from 1100p), Wizard Guild requirement increased to 10% (from 9%) per point
- Dwarf Warrior: -10r (from 110r)
- Firewalker: construction cost reduced to -10% (from -7.5%), explore cost increased by 5%
- Gnome Juggernaut: -50p (from 775p)
- Orc: +20% food consumption removed
- Orc Bone Breaker: 4/3, +1 OP for every 375 prestige, max +3 (from 5/2, +1 OP for every 625 prestige, max +2)

## [1.4.3] - 2021-07-04
### Changed
- Locked and abandoned dominions no longer appear on the Search page
- You can no longer destroy buildings or rezone land to reduce your defense by more than 15%

### Fixed
- Prestige loss is now correctly calculated by your recently invaded count instead of invader's
- Op Center now shows correct value for Harbor
- Defensive casualty bonuses from techs are now properly applied

## [1.4.2] - 2021-06-03
### Added
- Solo players can join a pack prior to realm assignment
- The Scribes have been updated with additional information

### Changed
- Op Center is now the default Advisors page

### Fixed
- Bots will no longer overtrain spies and wizards
- Status page now correctly rounds boats down always
- Invasions outside of Elite Guard range no longer affect Prestige/School penalties

## [1.4.1] - 2021-05-23
### Added
- Abandon dominion feature is now available on the government page

### Changed
- Goblin Hobgoblin: additional +25p (now 1050p)
- Forest Haven: cost of spies reduced by 3.5% per 1% owned, max 35% (from 4% per 1%, max 40%)
- Wizard Guild: cost of wizards and archmages reduced by 3.5% per 1% owned, max 35% (from 4% per 1%, max 40%)
- The 48 hour cooldown for redeclaring war after canceling will be ignored if the target realm has declared war back during that timeframe
- Mutual war now increases infamy gains by 20%
- Mutual war now decreases resilience gains by 50%

### Fixed
- Sidebar menu notifications will now be cleared immediately when visiting the page
- Bots no longer overbuild ore mines by 10x

## [1.4.0] - 2021-05-09
### Changed
- Several types of actions can no longer be performed during the hourly tick in order to prevent unintuitive behavior/timestamps. Anything that requires a target (info/black ops, wonders) or goes into queue (construction, exploration, military training, and self spells) will be prevented during the first 3 seconds of the hour (:00-:02) with invasions requiring an additional 2 seconds (:00-04)
- All bonuses are now additive with one another with the following exceptions that remain multiplicative with one another: max population from prestige / other sources, construction cost from invasions / other sources, and casualty reductions from unit / non-unit sources
- Construction cost reductions are now capped at 75% before invasion discount
- Base prestige gain reverted to 20 (from 22.5)
- Schools penalty due to recent invasions reduced to -15% per invasion, max -75% (from -20% per invasion, max -80%)
- Towers improvement coefficient reverted to 5000 (from 4000)
- Towers/Harbor improvements increased to max 60% (from 40%)
- Harbor boat production/protection reduced to 1.25x relative to food production (from 2x)
- Sabotage boat damage increased to 2.4% (from 2.25%)
- Black ops will now be available on Day 7 (instead of Day 8)
- Every 100 points of mastery (up to 500 per type, rounded down) will add 50 to the minimum infamy after decay (max 500)
- The +10 infamy gain bonus changed to targets >75% (from >75% and <133%)
- Firewalker: construction cost bonus reduced to -7.5% (from -10%)
- Goblin Hobgoblin: +50p (from 975p)
- Human Knight: -25p (from 1050p)
- Kobold Underling: +5p (from 245p)
- Kobold Beast: -15p (from 975p)
- Lizardfolk Lizardman: -15p (from 1100p)
- Lycanthrope: maximum population bonus increased to 10% (from 7.5%)
- Nomad Blademaster: -25p (from 1050p)
- Orc: removed 10% increased prestige gain bonus
- Spirit Ghost: -20p (from 900p)
- Undead Progeny: -20p (from 900p)
- Wood Elf Mystic: +25p (from 1125p)
- WaveHack's Expertise: boat capacity reduced to +3 (from +5)
- Weather Manipulation: lightning bolt protection increased to 12.5% (from 10%)
- Sneaky Spies: spy loss reduction increased to -15% (from -10%)
- Sleeper Agents: spy losses reduction decreased to -7.5% (from -12.5%)
- Spy Network: spy strength recovery reduced to +1 (from +1.5)
- Wizard Nexus: wizard strength recovery reduced to +1 (from +1.5)
- Dark Artistry: spy/wizard strength recovery reduced to +1 (from +1.5)
- Master of Efficiency/Resources/Discipline: military cost reduction decreased to -1.75% (from -3%)
- Menace: reworked, now adds +1 raw mana production per tower and an additional +2 for up to two war relations
- Forest Haven: disband spy damage and spy losses reduced by 2.5% per 1% owned, max 25% (from 3% per 1%, max 30%)
- Wizard Guild: spell cost and wizard losses reduced by 2.5% per 1% owned, max 25% (from 3% per 1%, max 30%)
- Masonries now protect 1% castle per 1% owned, up to a maximum of 10% at 10% owned (from 25% at 25% owned)
- Realm assignment will happen 48 hours prior to OOP (was 36)
- NPDs cannot be targeted by black ops or resource theft
- NPDs will start with more homes and behave less predictably

### Fixed
- Performance improvements from upgrade to Laravel 8
- Legacy spell caching removed (in favor of eager loaded relation)

## [1.3.2] - 2021-05-06
### Added
- Checkbox added to invade page to prevent accidental bottomfeeds

### Fixed
- Missing prerequisites for a few techs
- Better performance on town crier page
- Numerous bugs related to bots; including overtraining spies, not considering in-training troops in defense calculation (thus training continuously), adding incoming land twice (thus overtraining DP), starving themselves, etc

## [1.3.1] - 2021-03-16
### Added
- Production advisor now shows information about infamy and schools penalty from recent invasions

### Changed
- Black ops protection bonuses are now additive with resilience multiplied after
- NPD defense adjusted
- Numerous improvements to NPD behavior
- On the search page, 'My Range' in 'Limit' dropdown now excludes your realm
- Updated User Agreement
- Wonders grouped into tiers

### Fixed
- Various 500 errors, issues with casualties modifiers, schools penalty
- Smithies now reduce mana costs for Nox
- Can now change nickname in Realm Discord
- Remove race restrictions in Realm 0
- Allow Undo at hour 73
- Spells no longer magically disappear with Undo
- Daily bonuses can no longer be taken twice with Undo

## [1.3.0] - 2021-03-04
### Added
- Realm Assignment & Matchmaking!
- Dominions will now be placed in realm 0 upon registration and assigned a realm 36 hours prior to attacking being enabled (OOP). Packs will not be able to register during the 36 hours between realm assignment and OOP to preserve the quality of the matches
- Dominions will be assigned to 20+ realms with up to 8 packed players per realm based on previous performance. Creating a new account in an attempt to circumvent these measures is against the rules and will not be effective as new players will be assigned an above-average rating
- Undo button in protection
- Every realm now has a pre-made Discord server
- Non Player Dominions (bots) will now be assigned to realm 0
- Some bots will now begin to train/explore/etc
- Additional resources displayed in header overview
- Realm page shows usernames of players who share advisors
- Added spending statistics to production advisor
- Military modifier breakdown displayed on advisors/invade pages
- Rezone page now shows current percentage of each land type
- Added the hour number (1-24) to info ops for the day they were created

### Changed
- Op Center now shows current land/networth
- Military advisor improved and portions moved to military page
- Construction advisor moved to construction page
- Land advisor moved to explore page
- Tech/Castle advisors removed
- Technology page and Vision ops now show the total number of techs unlocked
- Rankings advisor now links to overall rankings pages
- Improved dominion history logs
- Guard Tower / Gryphon Nest: OP/DP bonus reduced to 1.6x per 1% owned up to a maximum of 32% at 20% owned (from 1.75x per 1%, max 35% at 20% owned)
- Temples: DP reduction reduced to 1.35x per 1% owned up to a maximum of 25% at 18.5% owned (from 1.5x per 1% owned, max 25% at 16.7% owned)
- Factories: Construction cost reduction now capped at 60% at 15% owned (from 75% at 18.75%), rezone cost reduction increased to 4x per 1% owned up to a maximum of 60% at 15% owned (from 3x per 1%, max 75% at 25% owned)
- Wizard Guilds: No longer excludes Dark Elf from wizard power bonus
- Orc: Savage +25r (from 0r)
- Orc: Guard -25r (from 25r)
- Orc: Voodoo Magi changed to flat 5 DP for 975p (from 3 +1 per 600 prestige for 830p)
- Orc: Bone Breaker changed to 5 +1 per 625 prestige (max +2) for 1150p (from 7 -1 per 10% GT of target for 1075p)
- Wood Elf: Druid +25p (from 1075p)
- Goblin: Gem investment bonus decreased to +15% (from +20%)
- Sylvan: Dryad changed to .25 wizard on offense/defense (from .2/.333)
- Dark Elf: Adept changed to .25 wizard on defense (from .333) but is effectively .3 with WG bonus
- Dark Elf: Adept +100p (from 1000p)
- Dark Elf: Spirit Warrior loses bonus +0.5 OP vs 95%
- Dark Elf: wizard power racial removed (was +10%)
- Halfling: Spy power bonus reduced to +25% (from +30%)
- Nox Fiend: -25r, +12 mana
- Nox Nightshade: -60r, +25 mana
- Sylvan Centaur: -35r, +20 lumber
- New Wonder: Fountain of Youth +2.5% maximum population
- New Wonder: Golden Throne +15% attacking prestige gains
- New Wonder: Ruby Monolith -7.5% casualties on offense and defense
- Gnomish Mining Machine changed to +15% ore production (from +10%)
- High Cleric's Tower - now kills ALL immortal units (including Spirit Warriors and Phoenix)
- Maximum prestige gain from destroying another realm's wonder reduced to 75 (from 100)
- Cyclone mana cost reduced to 3x (from 3.5x)
- The least impactful wonders have been reduced to 150k HP
- A few wonders will spawn at the start of the round, with the remaining spawning as usual
- Energy Mirror reflect chance reduced to 20% (from 30%)
- Spy/Wizard losses reduced by 20% during mutual war
- Archmages can now die on failed black ops at 1/10th the rate of wizards
- Infamy gain vs targets with relative ratios > 90% of yours increased by 5-10
- Infamy decay decreased to 20 per hour (from 20-25 sliding scale)
- Prestige loss increased by 1% for every invasion after the second in the previous 7 days, to a max of 15%
- School bonuses reduced by 20% for every invasion after the second in the previous 3 days, to a max of -80% effectiveness
- Siege Weaponry tech increased to 20% (from 10%)
- Ancestral Knowledge tech increased to 25% (from 15%)
- Public Baths tech increased to 30% (from 25%)
- Miser's Grasp tech increased to 25% (from 20%)
- Shipwright's Ingenuity tech increased to 12.5% (from 10%)

## [1.2.5] - 2021-01-20
### Changed
- Replace networth with land in overview top banner

### Fixed
- Updated infamy tooltip
- Bug with spy ops damage calculation
- Prevent mousewheel events in input fields

## [1.2.4] - 2021-01-17
### Added
- Show spy/wizard strength recovery rates in statistics advisor

### Fixed
- Nox racial was not being applied to the bonus invasion RP from schools
- Survivalist Mentality prestige perk was incorrect

## [1.2.3] - 2021-01-12
### Changed
- RPs from invasion decreased to 1000 (from 1100), but schools generate 130 per 1% owned up to a maximum of 2600 at 20% (from 39 per 1%, max 1560)
- Dwarf/Gnome: Racial spell now protects ore from earthquake
- Goblin Hobgoblin: -25p
- Orc: gains 10% additional prestige from invasions
- Lightning bolt damage increased to 0.41% (from 0.40%)
- Lightning bolt now damages Science instead of Towers (Harbor is also excluded)
- Masonries now protect 1% castle per 1% owned (from 0.75% per 1%), max remains at 25%
- War bonus to land/prestige gains reduced to 10% (from 15%), mutual war remains at 20%
- Infamy now boosts gem/ore/lumber production to a max of 3% (from 5% to gems only) in addition to platinum
- Infamy decay slightly reduced to 0.5% of current + 20 (from 25)
- Black op damage reduction is capped at 80% from all sources

### Fixed
- Draftees now included in RCL count
- RPs from invasion should not become negative on multiple previous invades

## [1.2.2] - 2021-01-05
### Added
- Added realm number to status page and ops center header
- Added racial perks to status page and ops center info pane

### Fixed
- Tributary System was incorrectly applying a flat 60% food production
- Removed troop cost abbreviations
- Battle reports now show additional information to realmies
- Notifications when land arrives after troops are home no longer display '0 units returned'

## [1.2.1] - 2021-01-03
### Added
- Show calculated damage reduction from resilience on status page and ops center
- Show calculated production bonuses from infamy on production advisor page

### Changed
- Removed black ops damage reduction based on war duration
- Removed wizard strength recovery bonus below 25% strength

## [1.2.0] - 2021-01-01
### Added
- New Stat: Infamy
  - Temporarily boosts platinum production (max 7.5%) and gem production (max 5%)
  - Gained when performing war operations
    - Increased by 5 per op
    - Bonus for targeting 75% (+10)
    - Bonus for higher ratio targets (+15-40)
  - Scales from 0 to 1000
  - Decays by 25 each hour
- New Stats: Spy Resilience, Wizard Resilience
  - Temporarily reduces damage taken by war operations (max 76%)
  - Gained when targeted by war operations
    - Spy Resilience is increased by 8 per op
    - Wizard Resilience is increased by 11 per op
  - Scales from 0 to 1000
  - Decays by 8 each hour
- New Stats: Spy Mastery, Wizard Mastery
  - Replaces Spy Prestige and Wizard Prestige rankings
  - Gained when performing war operations (Infamy / 10)
    - No gain when outclassing your target by 500 or more
    - Bonus when outclassed by your target (+1, -1 for your target)
    - Additional bonus when within 100pts of your target (+1, -1 for your target)
- New Tech Perks:
  - Tributary System, increases food production bonus from prestige by 60%
  - Anti-Magic Sigils, decreases duration of enemy spells by 1 hour
  - Menace, range-based infamy gain bonus expanded to 60%
- Many other techs have had their values adjusted or changed position in the tree
- Additional submit button at the top of the Technology page
- Timestamps in Op Center advisor

### Changed
- Removed prestige gain from war operations (replaced with Infamy)
- Dock protection now scales by 0.05/day after day 25 (from 0.1/day)
- Sabotage Boats damage increased to 2.25% (from 2%)
- Fireball damage increased to 2.65% from (2.5%)
- Magic Snare damage changed to 3.5% of current, min 1.5 (from flat 2%)
- Simplified the black ops success formula to a single variable
- Changed min/max success rates on black ops to 1%/95% (from 3%/97%)
- Changed min/max success rates on info/theft ops to 1%/99% (from 3%/97%)
- Spy losses from info ops limited to 0.006x LAND (0.003x LAND for Chameleon / Master Thief)
- Cyclone now has a 100% success rate
- Cyclone damage reduced to 1.5x wizards (from 3.5x) and 0.75% of max wonder health (from 2%)
- Cyclone no longer contributes toward wonder prestige gain (only attacking)
- Rebuilding a neutral wonder will no longer grant prestige
- Destroying a neutral wonder without rebuilding it will incur a penalty of 25 prestige to contributors in the realm that destroyed it
- Halls of Knowledge to spawn only in the first 14 days of the round
- Only one of the following wonders pairs spawn in a given round
  - Ancient Library/ Halls of Knowledge
  - Ivory Tower / Wizard Academy
- Ivory Tower and Wizard Academy no longer protect themselves from cyclone
- Imperial Armada now also grants -1% platinum tax from royal guard
- Horn of Plenty increased to +2% production to all resources (from +1%)
- Factory of Legends reduced to -20% construction platinum cost (from -25%)
- Base prestige gain on attacks increased to 22.5 (from 20)
- Maximum prestige decreased to 90% of land size (from 100%) or 250 whichever is higher
- Dark Elf Adept: Wizard Guild requirement increased to 9% (from 8%) per point to a maximum of +4/+4 (from +5/+5)

### Fixed
- War declarations in the town crier will now display the realm names as they were at that exact time

## [1.1.8] - 2020-12-10
### Fixed
- Destroying a neutral wonder while you already control a wonder no longer rewards prestige
- Using the calculator from in-realm advisors no longer sometimes uses your own info instead

## [1.1.7] - 2020-12-01
### Added
- Status advisor replaced with Op Center advisor
  - View all of you or your realm mates' info on a single page
  - Copy ops button for in-realm dominions
  - Load in-realm dominions into the calculator

### Fixed
- Offensive power tech bonuses corrected in calculator
- Urg Smash Technique destruction refund is now based on raw cost

## [1.1.6] - 2020-11-25
### Changed
- The displayed out-of-realm Wonder HP is now approximate (rounded to the nearest 10,000)

### Fixed
- Typo in Goblin gem investment racial bonus
- Additional significant digit in range calculations within dropdowns/op center
- The Maelstrom tech now also increases the max health cap on cyclone damage
- Fool's gold no longer protects lumber/ore unless you have the Trick of the Light tech

## [1.1.5] - 2020-11-15
### Fixed
- Nox racial tech bonus no longer applies to the portion of RP generated by attacks that is based on hourly school production
- A flawed code cleanup will no longer cause defensive casualties to sometimes be reduced to zero
- Corrected order of operations in NPD defense calculation

## [1.1.4] - 2020-11-11
### Changed
- New artwork on the homepage!
- Updated links to the wiki
- Military Culture tech changed to +10% (from 5%)
- Prestige gains on attack are now reduced by 10% per recent invasion, to a minimum of 20 prestige
- Attacks against targets 75%+ your size now generate 150% of your hourly research point production in addition to 1100 base (from flat 1000)
- Research point gains on attack are reduced if _you_ have been invaded many times recently (20% less for each invasion after the 2nd)

### Fixed
- Wrong prerequisite for Ares' Favor tech

## [1.1.3] - 2020-11-05
### Fixed
- Errors during invasion (related to RP/boat refactor)
- Missing perk on Urg Smash Technique tech
- Missing description for Night Watch tech
- Fearless Adventurers tech no longer sets morale to zero
- Update prerequisite text

## [1.1.2] - 2020-10-31
### Added
- Visual Tech Tree for selected dominion added to Technology Page
- Direct registration link on homepage
- Halloween icon/achievement

### Fixed
- Op Center archive no longer redirects to status page
- Missing perk for the Night Watch tech

## [1.1.1] - 2020-10-30
### Fixed
- Bug with exploration draftee cost
- Bug with RP production from schools
- Quickstarts updated with the new research point totals
- Morale generation is now +6 below 80% (from +6 below 70%)

## [1.1.0] - 2020-10-29
### Added
- Brand new Tech system!
  - Number of techs increased from 27 to 66 (with smaller bonuses)
  - Tech cost changed to a flat 10,000
  - Platinum bonus now rewards 750 RPs
  - Schools now generate 26 RP/hour per 1% owned (max 40%)
  - RPs from invasion changed to MAX(1000, daysInRound/0.03), halved for hits under 75%, none for hits under 60%
- Three new wonders:
  - Hanging Gardens: +20% food production
  - Gnomish Mining Machine: +10% ore production
  - Horn of Plenty: +1% platinum/lumber/ore/food/mana/gem production
- Threads with new posts will be displayed in bold on the first page load
- Added additional data to incoming land/building display in advisors/ops

### Changed
- Maximum pack size changed to 4 (from 5)
- Maximum packed players per realm changed to 6 (from 7)
- Cooldown before redeclaring war on same realm increased to 48 hours (from 24)
- Prestige gain is no longer reduced due to recent invasions
- NPD defense above 525 acres reduced slightly
- Wonder power on respawn is now rounded to the nearest 10,000
- Rebuilding neutral wonders now provide 25 prestige for the entire realm
- Destroying a wonder when you already have one only awards 25 prestige
- Neutral wonders will now have an HP cap of daysInRound*25000 (min 175k, max 500k)
- Most Wonders Destroyed title removed
- Max wonders available changed to: Realms * 0.4 (from 0.5)
- Cyclone damage now capped at 2% of a wonders max HP (from 1.5%)
- Base cyclone damage changed to 3.5x max(wizards,Acres) (from 5x)
- Wonder invasion casualties reduced to to 3.5% casualties (from 5%)
- Energy Mirror: mana cost increased to 4.5x (from 4x), reflection chance increased to 30% (from 20%)
- Vision: mana cost decreased to 0.5x (from 1x)
- Goblin: +5 population from barren acres removed, +10 gem production bonus removed, castle improvement bonus changed to +20% for gems only (from +10% for all)
- Kobold: +5 pop on barren acres removed, population growth bonus reduced to +10% (from +20%)
- Human Cavalry: +25p
- Human Knight: +25p
- Icekin: +5% platinum production, AM cost reduction reduced to -100p (from -175p)
- Lizardfolk Chameleon: +25p
- Lizardfolk Lizardman: -50p
- Lycanthrope: maximum population increased to +7.5% (from +5%)
- Nox Nightshade: -40r, +1 DP, DP increased by 1 per 12% swamp max +3 (from 1 per 10% max +4)
- Sylvan Centaur: -35r
- Troll Basher: +1 OP, +150p, loses race-targeting bonuses
- Troll Smasher: -25p, loses race-targeting bonuses
- Wood Elf Druid: +25p
- Wood Elf Longbowman: +30p

### Fixed
- Wonder prestige gains and damage are now rounded to the nearest integer

## [1.0.10] - 2020-10-07
### Added
- Additional messageboard and improved some rankings icons
- Pagination added to council/forum/board threads

## [1.0.9] - 2020-10-03
### Added
- New Message Boards tied to user accounts
- Able to select an avatar based on previous rounds' rankings
- Pagination added to council and forum index

### Changed
- Renamed Global Forum to Round Forum

### Fixed
- Recently invaded calculation required 25 hours to clear instead of 24
- Attacking wonders under certain conditions no longer breaks the town crier
- Wonders now appear in alphabetical order in the dropdown
- Race name no longer interferes with search in dominion dropdowns

## [1.0.8] - 2020-09-24
### Added
- Disclaimer on calculators page

### Changed
- Race name added to op center json data

### Fixed
- Boat protection from docks was being calculated incorrectly
- Small tweaks to automated stat export and moderator tools

## [1.0.7] - 2020-09-22
### Added
- Status advisor for realmies
- Most recent invasions for a dominion are visible in the op center
- Copy op center data in JSON format
- Sidebar menu indicator for unbuilt land
- Sidebar menu indicator for unseen town crier events
- Sidebar menu indicator for unseen wonders
- Button to load your temples into defense calculator

### Changed
- The recently invaded message in clear sights will now show the exact number of invasions

## [1.0.6] - 2020-09-17
### Changed
- Added more boats to quick starts, removed Ares from hour 61 versions

## [1.0.5] - 2020-09-08
### Added
- Offensive actions warning on the last day of the round 
- Show wonder in realm info box

### Changed
- Remove 'beta' from new round names
- The dashboard now shows dominion land/networth

### Fixed
- Several errors when attacking wonders
- Onyx Mausoleum perk was negative
- Reduced db queries on calculators page
- Adjusted Discord widget positioning

## [1.0.4] - 2020-09-07
### Changed
- Mindswell removed
- Tech cost will now increase if 50% of your total conquered acres is greater than highest land achieved (from 35%)
- Increased maximum prestige gain from destroying wonders to 125 (from 100)
- Halls of Knowledge will no longer spawn on Day 6
- Reduced defense for NPDs under 500 acres
- Cyclone damage capped at 2.5%
- Updated README

### Fixed
- Nightly backups are now running correctly
- Adjustments to StyleCI integration

## [1.0.3] - 2020-09-03
### Added
- Active spell counter in sidebar
- Town crier tooltips now include the target's race

### Changed
- Maximum prestige gain from destroying wonders increased to 125 (from 100)
- Minimum defense changed to 3x LAND unmodded (from 5x LAND-150 plus mods)
- Defense required to OOP under 600 acres is now 3x LAND
- Defense required to OOP at or above 600 acres is now 5x LAND (from 5x LAND-150 plus mods)
- Halls of Knowledge will no longer be guaranteed to spawn on Day 6

### Fixed
- The Onyx Mausoleum now has the correct perk
- Cyclone error messages
- The Graveyard can no longer have a monarch, participate in wars, or attack wonders

## [1.0.2] - 2020-08-31
### Added
- Show effect of morale on invasion page

### Fixed
- 5:4 calculation for wonders no longer uses OP mods
- Plunder will no longer remove the target's resources
- Adjusted wording in town crier/incoming resources

## [1.0.1] - 2020-08-30
### Added
- Range tooltips in town crier
- Date dividers in town crier

### Fixed
- Errors in recently invaded calculation
- Errors on Calculators page
- A problem where NPDs were spawning in realm 0
- Problems with several quickstart files

## [1.0.0] - 2020-08-27
### Added
- Wonders of the World: Capture wonders to gain bonuses for your realm
  - The first wave of wonders will spawn on Day 6
  - An additional wonder will spawn every 48 hours after the first wave
  - Deal damage using your military or wizards
  - After a wonder is rebuilt by a realm, its current power is visible to all players; otherwise you will only see its maximum power
- Quick Start: Create your dominion from a template. You can customize the final 12 ticks of protection or skip it entirely
- Non-Player Dominions: Two additional bots will be added to each realm (for a total of 4)
- New Spell: Mindswell - generates research points when attacking a wonder
- New Spell: Cyclone - deals damage to wonders
- New Rankings/Titles: The Demolisher (wonder attack damage), The Aeromancer (wonder spell damage), The Opportunist (wonders destroyed)
- War Bonus status is now displayed on the government and realm pages
- New table under Military Advisor for incoming resources (prestige, research points, platinum, gems and boats)

### Changed
- Recently invaded is now calculated using ticks instead of hours
- Generated land, prestige, and research points will no longer be awarded after the 2nd hit by your dominion on the same target within 8 hours
- Attacks required a minimum of 80% morale (from 70%)
- Research Points gained on attack down to 15 OR daysInRound/2, whichever is HIGHER (from 17)
- Tech cost will now increase if 35% of your total conquered acres is greater than highest land achieved
- Snare damage reduced to 2% (from 2.5%)
- The damage of war spells and operations will slowly decrease after 60 hours of war (to a minimum of 65% damage after 96 hours)
- One-way War: war operations now have a 25% chance to gain 1 prestige
- Mutual War: war operations yield +2 prestige for success, -1 for failure (from +2 only)
- Docks: boat protection is increased by 0.1 each day after Day 25 (from 2.5)
- Forest Havens: now produce 20 lumber per hour
- Wizard Guilds: wizard power bonus no longer affects Dark Elves
- Dark Elf: +10% wizard power
- Dwarf: Ore investment bonus increased to +15% (from +10%)
- Dwarf Miner: -5r
- Gnome: Racial spell changed back to Miner's Sight (from Mechanical Genius)
- Gnome Juggernaut: Offense reduced to 6, +0.5 vs 75%, +1 vs 85% (from 7)
- Goblin Hobgoblin: Plunder now steals an hour of platinum/gem production of the target, max 20p/5g per surviving unit (from 2% of stockpiled resources, max 50p/20g)
- Human Cavalry: -25p
- Human Knight: +25p
- Kobold Grunt: +10p, +5r
- Lizardfolk Chameleon: -25p
- Lizardfolk Lizardman: +100p
- Lycanthrope: maximum population reduced to +5% (from +10%)
- Lycanthrope Werewolf: Offense reduced to 3 (from 4)
- Nomad Valkyrie: -25p
- Nomad Blademaster: +25p
- Orc Bone Breaker: Offense decreased by 1 for every 10% guard tower of the target, max -1 (from max -2), +25p

### Fixed
- Improvement success message now properly displays the contribution to your castle
- Updated table column widths on a number of pages
- Most Land Conquered and Explored rankings are now reduced by land lost
- Rankings will now update each dominion's realm name

## [0.10.0-8] - 2020-08-11
### Added
- Difficulty ratings added to race selection

### Changed
- The Town Crier is no longer be limited to 7 days
- Calculate from Op Center will no longer include outdated barracks spies

## [0.10.0-7] - 2020-07-31
### Added
- Advisors can now be shared with realmies outside of your pack from the government page
- You can now set a 'preferred resource' as the default for investment

## [0.10.0-6] - 2020-07-30
### Added
- Improvements fields can now be pre-populated with max resources

### Fixed
- National bank input will no longer prepopulate with 0
- National bank success message now shows the types/amounts exchanged

## [0.10.0-5] - 2020-07-28
### Added
- Added button to load op center data into Calculators

## [0.10.0-4] - 2020-07-24
### Added
- New Castle Advisor shows improvements and techs (visible to packmates)
- Info Ops will now display the day of the round that they were taken

### Fixed
- Realm counts corrected in Town Crier dropdown and Realm select input box
- Server time ticker will no longer appear behind other elements on mobile
- Any link to a packmate's ops center will redirect to their advisors page

## [0.10.0-3] - 2020-07-10
### Fixed
- Several adjustments to moderator tools
- National bank will no longer throw javascript errors

## [0.10.0-2] - 2020-07-08
### Changed
- Magic snare damage reduced to 2.5% per op (from 3%)

### Fixed
- Round timer hours will display correctly when over 99 hours remaining
- Dashboard will now correctly show days remaining for registration
- Report a Problem window restyled in the classic dark skin

## [0.10.0-1] - 2020-07-04
### Added
- Sidebar now shows tick count until daily bonuses reset
- Dominions that remain inactive and under protection for three days will be moved to realm 0
- Dominions over 600 acres may no longer leave protection with less than the minimum defense
- Added footer link for reporting bugs/abuse
- Additional tools to detect cheating

### Changed
- Default draft rate set to 35%
- Round timer now counts down to OOP (start of day 4)

## [0.10.0] - 2020-06-25
### Added
- Two new Titles: the Indomitable (defending success) and the Defeated (defending failure)!

### Changed
- Maximum pack size increased to 5 (from 4)
- Maximum number of packed players per realm increased to 7 (from 5)
- Players in packs of maximum size must select 5 unique races
- Race changes are limited to 3 players of a specific race amongst all packed players in a realm
- NPDs per realm increased to 2, realm size increased
- You can no longer redeclare war on the same realm for 24 hours after canceling
- Prestige cap for invasion changed to a flat 150 (from 15% of current prestige)
- Spell mana costs: Revelation decreased to 1x (from 1.2x), Vision increased to 1x (from 0.5x), Fireball decreased to 3x (from 3.3x), Lightning Bolt decreased to 3.5x (from 4x)
- Harbor Improvement: bonuses are doubled for boat protection/production
- Forest Havens: now increase spy power by 2% per 1% owned (max +20% at 10%), training cost reduction increased to 4% per 1% owned (max -40% at 10%)
- Wizard Guilds: now increase wizard power by 2% per 1% owned (max +20% at 10%), training cost reduction increased to 4% per 1% owned (max -40% at 10%), spell cost reduction changed to 3% per 1% owned (max -30% at 10%)
- Dark Elf: Removed +10% wizard strength
- Dark Elf Adept: Wizard Guild requirement decreased to 8% (from 10%) per point
- Lycanthrope: Homes moved to forest (from cavern)
- Orc Voodoo Magi: Added reduces combat losses, prestige requirement decreased to 500 (from 600) per point, +30p
- Sylvan: Removed -10% rezone cost, Warsong removed, new spell Verdant Bloom: 35% of conquered acres are automatically rezoned to forest
- Sylvan Centaur: Offense increased to 5.5 (from 5)

## [0.9.0-11] - 2020-06-11
### Added
- Additional mechanisms for detecting cheating

### Fixed
- Rapidly triggering spy/wizard operations should no longer allow you to drop below 25% strength

## [0.9.0-10] - 2020-06-01
### Added
- Announcements section added to the global forum
- Ability to view packmates' advisors (can be disabled in settings)
- Tooltips on Status page and Op Center

## [0.9.0-9] - 2020-05-23
### Fixed
- Additional pageload optimizations
- Networth calculations will now be more reliable
- Daily land bonus will now properly increase tech cost
- Squashed several bugs related to Orc invade screen calculations
- Max population (raw) in statistics advisor now includes barracks

## [0.9.0-8] - 2020-05-15
### Added
- Offense Calculator added to Calculators page
- Town Crier is now paginated (100 per page)

### Fixed
- Improved the networth calculation from the previous update

## [0.9.0-7] - 2020-05-11
### Fixed
- Optimized networth calculation, greatly improving search/realm page performance

## [0.9.0-6] - 2020-05-10
### Fixed
- Range queries optimized on Search/Invade/Espionage/Magic pages
- Locked dominions can no longer view the Op Center

## [0.9.0-5] - 2020-05-06
### Added
- You can now change your dominion and ruler names when restarting

## [0.9.0-4] - 2020-05-05
### Added
- Added User Agreement at round registration
- Added rules enforcement mechanism

### Fixed
- Cleaned up some information display referencing round start time

## [0.9.0-3] - 2020-05-02
### Fixed
- Defense calculator will now treat wizard guilds correctly
- NPDs can no longer have negative incoming troops
- NPDs will no longer send notifications to other players
- Rankings update will now calculate correctly based only on the current round
- Attempting to leave protection during the lockout period no longer results in a 500 error

## [0.9.0-2] - 2020-05-01
### Added
- Added title icons to top ranked dominions in Rankings
- Added titles to Rankings Advisor

### Fixed
- Defense calculator will properly apply temples to final defense
- Restarting your account will now reset techs and notifications
- Daily bonuses will now reset when you finish your protection instead of waiting until OOP

## [0.9.0-1] - 2020-04-30
### Fixed
- Invade button will actually be disabled at round end
- Restarting your account no longer circumvents racial pack restrictions
- Display some missing ranking titles/icons
- Cleaned up various UI issues

## [0.9.0] - 2020-04-30
### Added
- Click-Through Protection!
- You can now perform all of your protection logins at your own pace during the first three days of the round
- You can now change your race when restarting your account during protection
- One non-player dominion will now be added to each realm on the third day of the round
- Added an experimental Defense Calculator!
- Daily rankings have been reworked! Most of the existing valhalla rankings will now be recorded during the round
- Players holding the top spot in certain rankings will now gain a new title and avatar when posting on the Global Forum!
- New Rankings Advisor page will show all of your current standings

### Changed
- Icekin Ice Elemental: offense increased by 1 for every WPA (from 0.85 for every WPA)
- Land loss/generation ratio changed to 90:60 (from 85:65)
- Base defensive casualties changed to 4.05% (from 3.825)
- Base conversion rate from casualties changed to 1.65x (from 1.75x)
- Construction discount from lost buildings no longer stacks with the discount gained from successful attacks
- Minimum defense increased to 5 x [Land - 150] (from 1.5 x Land)
- Adjusted font colors in Town Crier to better show in-realm events
- Pressing 'Enter' on the invasion page will no longer submit the form

### Fixed
- Increased the size of some input fields on mobile
- Fixed a bug where Bashers could kill Spirit/Undead
- Fixed a bug where Clerics didn't always kill Spirit/Undead with fewer casualties tech

## [0.8.1-7] - 2020-04-29
### Fixed
- After offensive actions have been disabled for the round, buttons for initiating these actions (invade, explore, hostile spells, and hostile spy ops) will be disabled

## [0.8.1-6] - 2020-03-29
### Fixed
- Improved pageload time
- Prevent exploits due to race conditions

## [0.8.1-5] - 2020-03-21
### Added
- Added unread count badge to the forum page menu item in the sidebar to indicate new messages since your last forum visit
- Added links to other realms in the war table on each realm page
- Dominion dropdowns will now show the target's race

## [0.8.1-4] - 2020-03-18
### Fixed
- Attempting to recast spells shortly after hour change no longer causes a 500 error
- Removed error message when attempting to send under 50% of target's defense

## [0.8.1-3] - 2020-03-18
### Fixed
- Links to Dominions without any ops no longer redirect to op center
- Battle reports for overwhelmed invasions no longer cause an error
- 33 percent rule no longer adds draftees to invasion force DP
- Fixed a bug where 33 percent rule could be ignored
- Prevent Dwarf Cleric 'kills_immortal' perk logic from affecting non-immortal unit casualties

## [0.8.1-2] - 2020-03-13
### Fixed
- Daily land bonus no longer overwrites research point total
- Self spells no longer increasing spell failure statistic

## [0.8.1-1] - 2020-03-11
### Fixed
- Removed 'Partially Implemented' tag from Towers improvement
- Prevent possible exploits due to race conditions

## [0.8.1] - 2020-03-08
### Added
- Spirit and Nomad are playable once again
- Global Forum for all dominions
- War information on each realm page
- New magic and espionage statistics
- Valhalla pages for each user
- A dominion can now be restarted prior to the first tick
- Late signups will now be awarded additional starting resources after the third day of the round
- Attacking/exploring/blackops will now be disabled 9-18 hours before the end of the round (from 1-16)
- Mass exploration restriction: you can no longer explore for more than 50% of your current land total
- Excessive release restriction: you can no longer release more than 15% of your defense within a 24 hour period

### Changed
- Dominion names must contain 3 consecutive alphanumeric characters for searchability
- Maximum of 5 packed players per realm (4 packs no longer land with 2 packs)
- Info ops spy/wizard strength cost reverted to 2% (from 1%)
- Reduced damage and cost of Disband Spies to 1.5% and 4.3x (from 2% and 5x)
- War cannot declared during the first 5 days of the round (up from 3)
- Wizard strength refresh rate increased by 1% when below 30%
- Mana production bonus added to Towers improvement and increased rate of investment
- Wizard Guilds now reduce losses on failed black op spells by 3% per 1% owned up to a maximum of 30% at 10% owned
- Forest Havens reworked
  - Spy Strength refresh rate increased by 0.1% per 1% owned, up to a maximum of 2% at 20% owned
  - Spy training reduced by 2% per 1% owned, up to a maximum of 40% at 20% owned
  - Fireball protection increased to 10% per 1% owned
  - Platinum theft protection removed
  - Defense bonus removed
- Surreal Perception now applies to info ops, duration increased to 12 (from 8), cost reduced to 3x (from 4x)
- Energy Mirror duration increased to 12 (from 8), cost increased to 4x (from 3x)
- Fool's Gold cooldown reduced to 20 (from 22)
- Research points gained on invasion reduced to 17 per acre (from 20 per acre)
- Tech cost multiplier reduced to 6.4 (from 6.426)
- Land bonus now awards 128 research points
- Attacks that fail by 85% or more no longer cause double casualties
- Prestige gains reduced on first hit, but increased on subsequent hits
- Prestige gains capped at 15% of your current prestige (before multipliers and base gain)
- Nox: research point generation bonus reduced to 10% (from 15%)
- Sylvan: Added -10% rezone cost
- Sylvan: Dryad changed to 1/3 wizard on defense (from 1/2)
- Dark Elf: Adept changed to 1/3 wizard on defense (from 1/2)
- Dwarf: ore investments increased by 5%
- Dwarf: Clerics now kill spirits and the undead
- Undead: Mana production increased to +10% (from 5%)
- Spirit: Food consumption increased to -80% (from -90%)

### Fixed
- Generated land from invasion will now count toward total land conquered
- Total number of realms no longer visible before round start
- Unfilled pack slots are now considered during realm assignment

## [0.8.0-7] - 2020-02-17
### Fixed
- Assassinate wizards will no longer work against undead
- Disband spies will now add the correct number of draftees
- War ops buttons are no longer dimmed when targeting a dominion that recently invaded you
- Percentage calculations in archived survey dominion operations have been corrected

## [0.8.0-6] - 2020-01-19
### Fixed
- Fixed a bug where exploration platinum costs were higher than expected upon joining the Elite Guard
- Fixed a bug where Snare caused a server error upon dealing negative damage

## [0.8.0-5] - 2020-01-19
### Fixed
- Fixed a server error when stealing gems

## [0.8.0-4] - 2020-01-18
### Fixed
- Black ops spell buttons are now grayed out before day 8
- Spy losses tech perk is now multiplicative instead of additive
- Failed spy/wiz operations no longer count as a success in statistics advisor
- Draftees no longer counted as attacking DP when checking 33% rule

## [0.8.0-3] - 2020-01-16
### Fixed
- Fixed a bug that sometimes prevented building discounted land
- Fixed a bug where casualty reduction techs were too powerful

## [0.8.0-2] - 2020-01-14
## [0.8.0-1] - 2020-01-14
### Added
- Added techs to the Scribes

### Fixed
- Fixed an issue where the Town Crier dropdown excluded the last realm
- Fixed a server error on Master of Water Valhalla page
- Fixed an issue with decimals places on invasion page
- Fixed duplicate sentence periods at end of unit descriptions
- Fixed missing race perks on registration and scribes pages
- Fixed starvation casualties not killing off the intended unit types
- Fixed a bug regarding invading with mixed 9 and 12 hour returning units causing prestige and resource points to incorrectly return faster than intended

## [0.8.0]
### Added
- War & Black Ops!
- Monarchs may now declare WAR on other realms
- War immediately allows the use of war-only black ops
- After 24 hours, 5% OP is added to attacks between the two realms (10% for mutual war)
- Mutual war also awards prestige for successful black ops between the two realms
- New Spell: Energy Mirror reflects spells back at the caster
- Technological Advances!
- Schools and invasion now reward research points
- Use research points to unlock bonuses from the tech tree (minimum cost based on highest land achieved)
- New Spell: Vision reveals your target's techs

### Changed
- Gnome: Racial spell changed back to Mechanical Genius (from Miner's Sight)
- Merfolk: Added +5% offense racial
- Sylvan Centaur: -20r, casualty reduction increased to -25% (from -15%)
- Nox: Added +15% research point generation
- Info ops spy/wizard strength cost reduced to 1% (from 2%)
- Additional discounted land added when constructed buildings are lost to invasion
- Defensive casualties reduced by target's relative land size (below 100%)
- Prestige gain increased
- Packs with only two players may now be assigned to a realm with other packs

### Fixed
- Theft success formula adjusted
- Starvation will now correctly kill units proportionally
- Population growth will now stop while starving
- Fix for prestige returning with 9hr units
- Firewalker construction cost bonus with max factories adjusted

## [0.7.1-19]
### Added
- Releasing units with DP will be hindered when:
  - Invaded in the last 24hrs
  - Having troops returning

## [0.7.1-18]
### Added
- Sending out less than 50% of defenders DP will prevent an attack

### Fixed
- Mefolk does not sink boats on overwhelmed attacks
- Use new tooltip style for buildings from Survey Dominion

## [0.7.1-17]
### Fixed
- Fix an issue with the search page Limit values being flipped
- Update daily bonuses in a single query and prevent a partial update in the event of an error
 
## [0.7.1-16]
### Fixed
- Added validation for negative values in posts

## [0.7.1-15]
### Fixed
- Default ordering in Op Center should now be on last op

### Changed
- Town Crier
  - Will only show last 3 days
  - Can be filtered by realm, via dropdown
  - Realm numbers now redirect to realm page
- Search
  - Own realm are visible in results
  - Default filtering will be all results now

## [0.7.1-14] - 2019-11-16
### Fixed
- Incoming buildings will now only be counted once when calculating maximum population at HC
- Total production of gems did not display on statistics advisory

## [0.7.1-13] - 2019-11-12
### Added
- Skin selection with a new DC theme

### Fixed
- Rankings on front page now reflects last round, as long as new round has not started yet

## [0.7.1] - 2019-11-06
### Added
- Added new races: Kobold and Orc
- Added monarchy: Each realm's elected monarch has the power to change the realm name, post a message of the day, and delete council posts
- Added dominion search page
- Added new categories to statistics advisor and valhalla
- Added back spell mana cost of active spells to magic page
- Added spell recharge time to magic page
- Top 10 land rankings from current round will now be visible on start page

### Changed
- Gnome Juggernaut: OP changed to 7 regardless of range
- Undead: Decreased max population bonus from +15% to +12.5%
- Undead Vampire: Now converts into elite dp at 65%+ (from 60%+) 
- Wood Elf Longbowman: +25p
- Wood Elf Mystic: +50p
- Wood Elf Druid: -50p
- Nomad: Removed
- Spirit: Removed
- Shrines: bonus increased to 5x (from 4x)
- Slightly increased prestige gains
- Reintroduce prestige loss for hits under 60% and multiple BF hits on the same dominon
- Cut spy losses in half for info ops
- Land generation changed to 85:65 (from 75:75)
- Base defensive casualties changed to 3.825% (from 3.375%)
- Conversion multiplier change to 1.75% (from 2%)
- Adjusted explore platinum cost formula
- Sending less than 85% of your target's defense will no longer cause defensive casualties
- Failed invasions when sending over 85% of the target's defense will now properly reduce defensive casualties for subsequent invasions
- Slightly tweaked starvation casualties to now kill off population types based on proportion
- Significantly increased the speed of the hourly tick (hour change)
- Scribes now contains more information. Construction, Espionage and Magic have now been added
- Other realms are now hidden before the round starts
- Updated racial descriptions for a lot of races

### Fixed
- Barracks Spy should now be more clear that draftees are inaccurate
- Fixed a bug when knocking a target outside of your applied guard range would reset your guard application
- Chameleons and Master Thieves now die on failed spy operations
- Fixed a bug where Survey Dominion calculated percentages based on a dominion's current land total
- Fixed a bug where races with increased max population from barren land wasn't applied properly
- Fixed a bug where Erosion reduced land gains
- Fixed spell duration in success message
- Fixed a bug where you could leave a guard immediately after joining
- Reduced Combat Losses (RCL) unit perk now correctly triggers on offensive casualties based on RCL units which were sent out, instead of RCL units at home
- Minor text fixes

## [0.7.0-10] - 2019-08-20
### Fixed
- Surreal Perception now states it lasts for 8 hours
- Fixed Clairvoyances sometimes disappearing from the Op Center
- Fixed notifications not updating properly on settings page
- Fixed not being able to the Royal Guard at the intended day in the round
- Fixed being able to leave the Royal Guard while in the Elite Guard

## [0.7.0-9] - 2019-08-18
### Fixed
- Fixed Parasitic Hunger not properly giving the +50% conversions bonus

## [0.7.0-8] - 2019-08-18
### Fixed
- Fixed theft buttons being clickable before theft is enabled in the round

## [0.7.0-7] - 2019-08-17
### Fixed
- Minor bug fixes

## [0.7.0-6] - 2019-08-17
### Changed
- Slightly increased prestige gains for attackers. Prestige loss for defenders unchanged
- Offensive actions and exploration will now be randomly disabled at end of round (1 to 16 hours before round end)

## [0.7.0-5] - 2019-08-14
### Changed
- Temporarily changed so that new dominions always land in the most emptiest realm

## [0.7.0-4] - 2019-08-14
### Fixed
- Fixed Government page styling on mobile
- Fixed Op Center page being slow sometimes
- Fixed 33% rule sometimes not being applied correctly
- Fixed incorrect dominion placement in realms if a pack was already present in such realm
- Minor text fixes

## [0.7.0-3] - 2019-08-11
### Changed
- A bunch of empty realms now get created for each new round, to prevent people landing together when not packing
- Land lost from being invaded is now again proportional to land types, including constructed/constructed buildings
- Increased spy/wiz success rate
- Race pages on the Scribes now show the race's home land type

### Fixed
- Units in training now count towards max military population

## [0.7.0-2] - 2019-08-09
### Added
- Added link to scribes in the top navigation bar

### Changed
- Dwarf Cleric: -40p
- Gnome: Now has Miner's Sight as racial spell. Mechanical Genius has been removed
- Gnome Juggernaut: Increased max staggered OP to +2.5 at 90% land
- Icekin: Removed +5% platinum production, ArchMage -25p
- Lycanthrope Werewolf: -25p, +1 OP
- Nox Nightshade: +50p
- Nox Lich: -50p
- Spirit: Increased max population bonus from +12.5% to +15%
- Spirit Phantom: No longer needs boats 
- Spirit Banshee: No longer needs boats
- Spirit Spectre: Now converts into elite dp at 60%+ (from 65%+)
- Undead Skeleton: No longer needs boats
- Undead Ghoul: No longer needs boats
- Undead Vampire: Now converts into elite dp at 60%+ (from 65%+) 

## [0.7.0-1] - 2019-08-09
### Fixed
- Fixed some deploy-related stuff

## [0.7.0] - 2019-08-09
### Added
- Added new races: Lycanthrope, Merfolk, Nox*, Spirit, Undead, Wood Elf
- *Note: The Nox was a premiun race back in Dominion Classic. In OpenDominion it has been renamed to just 'Nox', and made available for everyone, without restrictions
- Added missing Valhalla races, including the ones mentioned above
- Construction advisor now shows total amount of barren land
- Added info op archive, allowing you to view previously taken info ops
- Docks are now fully implemented, preventing a certain amount of boats from being sunk
- Notifications are now visible from the status screen in a 'Recent News' section
- Added theft espionage operations, allowing you to steal resources from your target
- Added magic spells: Fool's Gold and Surreal Perception
- Added Government page with Royal Guard and Elite Guard
- Added basic Scribes page with races and units

### Changed
- Condensed the items in the left navigation menu (except on mobile)
- Removed prestige penalty on invading targets below 66% your size
- Added prestige grab on invading targets above 120% your size
- Extended the Statistics Advisor with more useful information
- No more than two identical races can be in the same pack 
- Changed national bank icon
- Realms can now have mixed racial alignments
- Significantly reduced starvation casualties
- Slightly lowered overall exploration costs
- Significantly increased exploration cost at or above 4000 acres
- Reduced land lost and defensive casualties upon being on the receiving end on a successful invasion. Total land gains for attackers unchanged
- Reworked spy/wizard operations success chance to be more linear
- Significantly increased spy casualties for failed info gathering operations
- Spirit/Undead and Human/Nomad now count as identical races for pack race-uniqueness purposes

### Fixed
- Fixed newlines sometimes not being properly applied in council posts
- The server time/next tick tickers should now be slightly more accurate
- Fixed Gnome's racial spell Mechanical Genius, now properly granting the intended amount of rezoning cost reduction
- Realm spinner on realm page no longer allows for invalid input (eg negative numbers) which in turn displayed a server error page
- Barren land now correctly houses 10 population for Gnome and Halfling
- Fixed bug where spy strength was lowered when trying to perform op when you had no spies
- Land lost from being invaded now properly takes barren land away first
- Minor text fixes
- Gnomes now correctly do not gain any ore reduction, from any sources, on their units

## [0.6.2-9] - 2019-07-14
### Changed
- Slightly improved targeted espionage/magic spell success rate

### Fixed
- Fixed spell mana cost not being reduced by wizard guilds
- Fixed a race condition during tick, where more resources could be deducted than intended
- Fixed displayed WPA on statistics advisor page
- Fixed a bug where you could still conquer land upon bouncing
- Fixed unable to scroll op center page tables on mobile
- Fixed typo on Town Crier page

## [0.6.2-8] - 2019-06-23
### Changed
- Server and tick timers on pages are now based on server time, not browser time
- Changed texts and colors on Town Crier page

### Fixed
- Fixed race condition bug around hour change, sometimes resulting in loss of resources when performing actions on the hour change
- Changed invasions to calculate casualties before everything else, fixes bugs related to immortal range and Hobgoblin plunder
- Fixed missing Wizard Guild spell mana cost reduction
- Fixed missing text on Town Crier page where a realmie fended off an attack
- Removed 'target was recently invaded'-text on invasion report on failed invasions
- Fixed Clear Sight not including returning boats
- Fixed networth calculation to include dynamic unit power values (e.g. increased op/dp from land ratio based perks)

## [0.6.2-7] - 2019-06-16
### Fixed
- Fix Clairvoyance reports on Op Center page
- Fix 5:4 check on the invasion page

## [0.6.2-6] - 2019-06-16
### Fixed
- Fixed Ares call not working properly sometimes

## [0.6.2-5] - 2019-06-16
### Added
- Added unread count badge to the council page menu item in the sidebar to indicate new messages since your last council visit

### Fixed
- Fixed unit OP/DP on military training page to show with including certain bonuses
- Fixed error where military DP was counted twice
- Fixed code refactor with SPA/WPA perks
- Fixed error in Op Center with Clairvoyance

## [0.6.2-4] - 2019-06-12
### Fixed
- Fixed Firewalker's Phoenix immortal except vs Icekin perk
- Fixed ArchMage cost reduction for Icekin

## [0.6.2-3] - 2019-06-12
### Added
- Added ability to delete your pack once during registration

### Fixed
- Fixed server error when trying to join a pack with invalid credentials
- Fixed missing unit perk help texts
- Fixed Regeneration racial spell for trolls

## [0.6.2-2] - 2019-06-10
### Fixed
- Fixed error on construction page
- Fixed realms not filling up properly with new dominions

## [0.6.2-1] - 2019-06-10
### Added
- Added Clairvoyance spell
- Added new races: Dark Elf, Gnome, Halfling, Icekin, Sylvan, and Troll

### Changed
- Changed timestamp displays from relative server time (eg '13 hours ago') to absolute server time (eg '2019-06-19 13:33:37'). A setting will be added in the future for this, including round time (eg 'Day 12 Hour 23')

## [0.6.2] - 2019-06-05
### Changed
- Changed realm size to 6 (from 12)
- Changed max pack size to 3 (from 6)
- Only one pack can now exist per realm
- Changed invasion range from 60-166% to 75-166% (until guards are implemented)
- Invasion reports can now only be viewed by people in the same realm as the invader and defender
- Data in the Op Center is now shown to the whole realm, regardless of range
- Failing a spell/spy info operation now keeps the target selected in the dropdown
- Changed relative land size percentage colors to make more sense
- Discounted acres after invasion are now only gained upon hitting 75%+ targets
- Minor text changes

### Fixed
- Fixed unit OP/DP rounding display issue in case of non-integer numbers (Firewalker Phoenix)
- Fixed an issue where failing an info op tried to kill off more spies than you had
- Fixed text when last rankings are updated

## [0.6.1-5] - 2019-05-14
### Changed
- "Remember Me" on login page is now checked by default
- Spy losses from failed ops now fluctuate slightly based on relative land size

### Fixed
- Fix a bug with checking whether certain spells are active, fixes the notorious 'Ares Call/DP bug'
- Town Crier page now shows invasions from/to your own dominion
- Amount of boats on status page and Clear Sight now also include returning boats from invasion

## [0.6.1-4] - 2019-04-16
### Changed
- Reduced failed espionage operation spy casualties from 1% to 0.1%

### Fixed
- Fixed dominion sorting in realm page on land sizes larger than 1k
- Units returning from battle are now included in population calculations
- Units returning from battle are now included in networth calculations
- Units returning from battle are now included on the military page under the Trained column

## [0.6.1-3] - 2019-04-14
### Fixed
- Fix packie name on realm page

## [0.6.1-2] - 2019-04-14
### Added
- Added username to realm page for dominions you pack with

### Changed
- Rankings now update every 6 hours (down from 24 hours)
- Remove ruler name from realm page

### Fixed
- Fixed certain realms not getting filled properly
- Several last-minute invasion-related fixes

## [0.6.1-1] - 2019-04-11
### Added
- Added barren land column to explore page

### Changed
- The 'current hour' counter in at the bottom now displays 1-24, instead of 0-23. This should also help out with BR's OOP sim to match the hours
- Dominions on the realm page are now also sorted by networth if land sizes are the same
- Removed invasion morale drop for defenders
- Changed column label 'Player' to 'Ruler Name' on your own realm page
- Minor text changes

### Fixed
- Fixed a bug where packies can close the pack they're in. Now only the pack creator can close it

## [0.6.1] - 2019-04-09
### Added
- Added the following races to Valhalla: Dwarves, Goblins, Firewalkers, and Lizardmen
- Added largest/strongest packs to Valhalla

### Changed
- Moved the 'Join the Discord'-button from status page to daily bonuses page

### Fixed
- Removed "round not yet started"-alert from homepage
- Fixed a bug where creating a pack is placed in a new realm, instead of an already existing and eligible realm
- Minor text fixes

## [0.6.0-2] - 2019-04-09
### Changed
- Updated info box on the magic page
- Added indicator for racial spells on magic page

### Fixed
- Fixed error when registering to a round with duplicate dominion name
- Fixed several tables with data not displaying properly on mobile
- Fixed rankings change column not visible on mobile

## [0.6.0-1] - 2019-04-09
### Fixed
- Fixed an error when registering to a round and creating a new pack

## [0.6.0] - 2019-04-08
### Added
- Added invasions!
- Added Town Crier page
- Clear Sight now mentions if the target was invaded recently, plus roughly how severely 
- Military units now have a role icon next to them
- Temples are now fully implemented, also reducing DP bonuses of targets you're invading
- Added current round information to the home page

### Changed
- Unit and building tooltips have been moved from the question mark icon, to on the name itself

### Fixed
- Fixed not getting a notification when preventing a hostile spell or spy operation

## [0.5.2-1] - 2019-01-27
### Fixed
- Fixed racial spells for Firewalker and Lizardfolk

## [0.5.2] - 2019-01-27
### Added
- Added new races: Firewalker and Lizardfolk

## [0.5.1-4] to [0.5.1-8] - 2018-10-04
### Fixed
- Fix user IP resolving when behind Cloudflare DNS with trusted proxies
- Dominion numbering on the realm page now correctly starts at 1, instead of 0

### Other
- Maintenance work

## [0.5.0-9] to [0.5.1-3] - 2018-10-02
### Fixed
- Trying to fix deploy errors

### Other
- Maintenance work

## [0.5.0-8] - 2018-10-01
### Changed
- Info gathering ops on Op Center page now show exact time upon hover. ([#337](https://github.com/WaveHack/OpenDominion/issues/337))
- Significantly reduced spy losses on failed ops

### Fixed
- Fixed networth sometimes showing incorrect values on realm page. ([#310](https://github.com/WaveHack/OpenDominion/issues/310))
- Fixed construction cost calculation. As a result, construction costs are significantly higher than before. Time to start building factories. ([#347](https://github.com/WaveHack/OpenDominion/issues/347))
- Barracks Spy now shows number of draftees. ([#331](https://github.com/WaveHack/OpenDominion/issues/331))
- Fixed an division by zero error if you have 0 peasants. ([#349](https://github.com/WaveHack/OpenDominion/issues/349))
- Various other issues

### Other
- Documentation update
- Refactoring
- Queue refactor!
- More refactoring
- Seriously, a lot of refactoring

## [0.5.0-7] - 2018-08-26
### Other
- Maintenance work

## [0.5.0-6] - 2018-08-26
### Changed
- Switched Information and Under Protection sections around on status page.([#313](https://github.com/WaveHack/OpenDominion/issues/313))

### Other
- Maintenance work
- Documentation update

## [0.5.0-5] - 2018-08-11
### Fixed
- Fixed cost rounding issues on wizard cost multiplier when wizard guilds were built
- Fixed a bug regarding population growth. ([#176](https://github.com/WaveHack/OpenDominion/issues/176))

## [0.5.0-4] - 2018-08-07
### Fixed
- Council pages now show ruler name instead of user name

## [0.5.0-3] - 2018-08-05
### Fixed
- Fixed creating a pack sometimes giving an error. ([#321](https://github.com/WaveHack/OpenDominion/issues/321))

## [0.5.0-2] - 2018-08-04
### Fixed
- Realm page now shows ruler name instead of user name. ([#309](https://github.com/WaveHack/OpenDominion/issues/309))
- Limit amount of rows of show on realm page in case realm size is less than 12. ([#308](https://github.com/WaveHack/OpenDominion/issues/308))

### Other
- Refactoring

## [0.5.0-1] - 2018-08-04
### Fixed
- Fix deploy error

## [0.5.0] - 2018-08-04
### Added
- Added new races: Dwarf and Goblin
- Added ruler name for round registration. ([#254](https://github.com/WaveHack/OpenDominion/issues/254))
- Added packs. ([#280](https://github.com/WaveHack/OpenDominion/issues/280))
- Added racial spells. ([#157](https://github.com/WaveHack/OpenDominion/issues/157))
- Added Op Center. ([#24](https://github.com/WaveHack/OpenDominion/issues/24))
- Added Espionage with info gathering operations: Barracks Spy, Castle Spy, Survey Dominion, and Land Spy. ([#21](https://github.com/WaveHack/OpenDominion/issues/21))
- Added Clear Sight spell. ([#220](https://github.com/WaveHack/OpenDominion/issues/220))
- Added Revelation spell. ([#221](https://github.com/WaveHack/OpenDominion/issues/221))
- Added unit OP/DP stats on military page ([#234](https://github.com/WaveHack/OpenDominion/issues/234))
- Added NYI/PI indicator and help text icon on Construction Advisor page
- Added Wizard Guild building, reducing wizard/AM plat cost and increasing wizard strength regen per hour. ([#305](https://github.com/WaveHack/OpenDominion/issues/305))
- Added failed spy op losses reduction on Forest Havens. ([#306](https://github.com/WaveHack/OpenDominion/issues/306))

### Changed
- Updated round registration page with better help texts and racial descriptions
- Employment percentage on status screen now shows 2 decimals
- Updated Re-zone Land icon in the sidebar
- Net OP/DP now scales with morale, down to -10% at 0% morale

## [0.4.2] - 2018-06-03
### Fixed
- Fixed dashboard page sometimes showing incorrect duration for round start/end dates
- Fixed internal server error on realm page using invalid realm number. ([#270](https://github.com/WaveHack/OpenDominion/issues/270))

## [0.4.1] - 2018-05-23
### Changed
- Updated `version:update` command to support Git tags

## 0.4.0 - 2018-05-22
### Added
- This CHANGELOG file

[Unreleased]: https://github.com/OpenDominion/OpenDominion/compare/1.42.0...HEAD
[1.42.0]: https://github.com/OpenDominion/OpenDominion/compare/1.41.3...1.42.0
[1.41.3]: https://github.com/OpenDominion/OpenDominion/compare/1.41.2...1.41.3
[1.41.2]: https://github.com/OpenDominion/OpenDominion/compare/1.41.1...1.41.2
[1.41.1]: https://github.com/OpenDominion/OpenDominion/compare/1.41.0...1.41.1
[1.41.0]: https://github.com/OpenDominion/OpenDominion/compare/1.40.2...1.41.0
[1.40.2]: https://github.com/OpenDominion/OpenDominion/compare/1.40.1...1.40.2
[1.40.1]: https://github.com/OpenDominion/OpenDominion/compare/1.40.0...1.40.1
[1.40.0]: https://github.com/OpenDominion/OpenDominion/compare/1.39.1...1.40.0
[1.39.1]: https://github.com/OpenDominion/OpenDominion/compare/1.39.0...1.39.1
[1.39.0]: https://github.com/OpenDominion/OpenDominion/compare/1.38.4...1.39.0
[1.38.4]: https://github.com/OpenDominion/OpenDominion/compare/1.38.3...1.38.4
[1.38.3]: https://github.com/OpenDominion/OpenDominion/compare/1.38.2...1.38.3
[1.38.2]: https://github.com/OpenDominion/OpenDominion/compare/1.38.1...1.38.2
[1.38.1]: https://github.com/OpenDominion/OpenDominion/compare/1.38.0...1.38.1
[1.38.0]: https://github.com/OpenDominion/OpenDominion/compare/1.37.2...1.38.0
[1.37.2]: https://github.com/OpenDominion/OpenDominion/compare/1.37.1...1.37.2
[1.37.1]: https://github.com/OpenDominion/OpenDominion/compare/1.37.0...1.37.1
[1.37.0]: https://github.com/OpenDominion/OpenDominion/compare/1.36.0...1.37.0
[1.36.0]: https://github.com/OpenDominion/OpenDominion/compare/1.35.1...1.36.0
[1.35.1]: https://github.com/OpenDominion/OpenDominion/compare/1.35.0...1.35.1
[1.35.0]: https://github.com/OpenDominion/OpenDominion/compare/1.34.1...1.35.0
[1.34.1]: https://github.com/OpenDominion/OpenDominion/compare/1.34.0...1.34.1
[1.34.0]: https://github.com/OpenDominion/OpenDominion/compare/1.33.0...1.34.0
[1.33.0]: https://github.com/OpenDominion/OpenDominion/compare/1.32.1...1.33.0
[1.32.1]: https://github.com/OpenDominion/OpenDominion/compare/1.32.0...1.32.1
[1.32.0]: https://github.com/OpenDominion/OpenDominion/compare/1.31.0...1.32.0
[1.31.0]: https://github.com/OpenDominion/OpenDominion/compare/1.30.1...1.31.0
[1.30.1]: https://github.com/OpenDominion/OpenDominion/compare/1.30.0...1.30.1
[1.30.0]: https://github.com/OpenDominion/OpenDominion/compare/1.9.0...1.30.0
[1.9.0]: https://github.com/OpenDominion/OpenDominion/compare/1.8.3...1.9.0
[1.8.3]: https://github.com/OpenDominion/OpenDominion/compare/1.8.2...1.8.3
[1.8.2]: https://github.com/OpenDominion/OpenDominion/compare/1.8.1...1.8.2
[1.8.1]: https://github.com/OpenDominion/OpenDominion/compare/1.8.0...1.8.1
[1.8.0]: https://github.com/OpenDominion/OpenDominion/compare/1.7.2...1.8.0
[1.7.2]: https://github.com/OpenDominion/OpenDominion/compare/1.7.1...1.7.2
[1.7.1]: https://github.com/OpenDominion/OpenDominion/compare/1.7.0...1.7.1
[1.7.0]: https://github.com/OpenDominion/OpenDominion/compare/1.6.0...1.7.0
[1.6.0]: https://github.com/OpenDominion/OpenDominion/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/OpenDominion/OpenDominion/compare/1.4.3...1.5.0
[1.4.3]: https://github.com/OpenDominion/OpenDominion/compare/1.4.2...1.4.3
[1.4.2]: https://github.com/OpenDominion/OpenDominion/compare/1.4.1...1.4.2
[1.4.1]: https://github.com/OpenDominion/OpenDominion/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/OpenDominion/OpenDominion/compare/1.3.2...1.4.0
[1.3.2]: https://github.com/OpenDominion/OpenDominion/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/OpenDominion/OpenDominion/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/OpenDominion/OpenDominion/compare/1.2.5...1.3.0
[1.2.5]: https://github.com/OpenDominion/OpenDominion/compare/1.2.4...1.2.5
[1.2.4]: https://github.com/OpenDominion/OpenDominion/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/OpenDominion/OpenDominion/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/OpenDominion/OpenDominion/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/OpenDominion/OpenDominion/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/OpenDominion/OpenDominion/compare/1.1.8...1.2.0
[1.1.8]: https://github.com/OpenDominion/OpenDominion/compare/1.1.7...1.1.8
[1.1.7]: https://github.com/OpenDominion/OpenDominion/compare/1.1.6...1.1.7
[1.1.6]: https://github.com/OpenDominion/OpenDominion/compare/1.1.5...1.1.6
[1.1.5]: https://github.com/OpenDominion/OpenDominion/compare/1.1.4...1.1.5
[1.1.4]: https://github.com/OpenDominion/OpenDominion/compare/1.1.3...1.1.4
[1.1.3]: https://github.com/OpenDominion/OpenDominion/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/OpenDominion/OpenDominion/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/OpenDominion/OpenDominion/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/OpenDominion/OpenDominion/compare/1.0.10...1.1.0
[1.0.10]: https://github.com/OpenDominion/OpenDominion/compare/1.0.9...1.0.10
[1.0.9]: https://github.com/OpenDominion/OpenDominion/compare/1.0.8...1.0.9
[1.0.8]: https://github.com/OpenDominion/OpenDominion/compare/1.0.7...1.0.8
[1.0.7]: https://github.com/OpenDominion/OpenDominion/compare/1.0.6...1.0.7
[1.0.6]: https://github.com/OpenDominion/OpenDominion/compare/1.0.5...1.0.6
[1.0.5]: https://github.com/OpenDominion/OpenDominion/compare/1.0.4...1.0.5
[1.0.4]: https://github.com/OpenDominion/OpenDominion/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/OpenDominion/OpenDominion/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/OpenDominion/OpenDominion/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/OpenDominion/OpenDominion/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/OpenDominion/OpenDominion/compare/0.10.0-8...1.0.0
