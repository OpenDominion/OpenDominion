# Hero Battle System

The Hero Battle system in OpenDominion is a strategic turn-based combat mini-game where players' heroes engage in tactical battles. This system adds an additional layer of strategy and character progression to the core dominion management gameplay.

## Overview

Heroes are specialized characters that players can develop alongside their dominions. These heroes can engage in various types of combat encounters, from direct player-versus-player battles to challenging raid encounters. The system emphasizes strategic decision-making, character development, and tactical combat.

## Hero Classes & Progression

### Available Classes

**Basic Classes (8 total):**
- Alchemist
- Architect  
- Blacksmith
- Engineer
- Farmer
- Healer
- Infiltrator
- Sorcerer

**Advanced Classes (2 total):**
- Scholar (requires 7,500 research points)
- Scion (requires 500 prestige)

### Level Progression
Heroes advance from level 0 to 12 through experience points gained from various dominion activities. Key milestones:
- Level 1: 100 XP
- Level 5: 1,500 XP
- Level 10: 6,000 XP
- Level 12 (maximum): 10,000 XP

### Class System Features
- Heroes can change classes with a 96-hour cooldown after round start
- Experience is retained from previous classes, providing passive bonuses at 50% effectiveness
- Each class offers unique benefits to dominion management and combat effectiveness

## Combat Statistics

Heroes have seven core combat statistics that determine their battle effectiveness:

1. **Health** - Starting and maximum hit points (base: 80 + 5 per level)
2. **Attack** - Base damage output (base: 40)
3. **Defense** - Damage reduction capability (base: 20, doubled when defending)
4. **Evasion** - Percentage chance to reduce incoming damage (base: 10%)
5. **Focus** - Bonus damage when using focus action (base: 10)
6. **Counter** - Additional damage when counter-attacking (base: 10)
7. **Recover** - Health restored when using recover action (base: 20)

## Battle Types

### Player vs Player (PvP) Battles
- **Direct Challenges**: Target specific players for combat
- **Queue System**: Automated matchmaking with similar-skill opponents
- **Rating System**: ELO-based skill tracking for competitive balance
- **Time Management**: 2-hour time bank per player for decision-making

### Practice Battles
- **Training Mode**: Fight against an "Evil Twin" NPC with identical stats
- **Risk-Free Learning**: No impact on combat rating or statistics
- **Skill Development**: Safe environment to experiment with tactics

### Tournament Battles
- **Organized Competition**: Round-robin or elimination tournaments
- **Extended Time**: 12-hour time bank for thoughtful play
- **Prestige Rewards**: Winners gain recognition and rewards

### Raid Encounters
- **PvE Combat**: Battle against themed encounter enemies
- **Story Integration**: Various encounter types (wolves, bandits, cultists, fire imps)
- **Objective Contribution**: Success advances raid goals

## Combat Actions

Heroes can perform one of five actions each turn:

1. **Attack** - Deal damage based on Attack stat, reduced by opponent's Defense
2. **Defend** - Double Defense stat for the turn, significantly reducing incoming damage
3. **Focus** - Enter focused state, adding Focus stat to next Attack's damage
4. **Counter** - Automatically retaliate with bonus damage when attacked
5. **Recover** - Restore health equal to Recover stat (reduces defense by 5 that turn)

## Combat Mechanics

### Turn Resolution
- All players choose actions simultaneously
- Players can queue multiple actions in advance
- Damage is calculated as Attack minus Defense (minimum 0)
- Evasion provides a chance to halve incoming damage

### Special Action Rules
- Focus, Counter, and Recover cannot be used on consecutive turns
- Only one focus state can be active at a time
- Heroes cannot heal above their maximum health
- Defending doubles defense but prevents other beneficial actions

### Strategy Types
Players can set overall battle strategies that influence automated play:
- **Balanced** - Even distribution of all actions
- **Aggressive** - Favors attack and focus actions
- **Defensive** - Emphasizes defend and counter actions
- **Counter** - Specializes in counter-attack tactics

## Victory and Progression

### Battle Resolution
- Battles continue until only one hero remains alive
- Simultaneous eliminations result in draws
- All combat results are tracked in hero statistics
- ELO-style rating adjustments maintain competitive balance

### Character Development
- Heroes gain experience from various dominion activities
- Upgrades unlock at even levels (2, 4, 6, 8, 10, 12)
- Building shrines enhances hero experience gain and effectiveness
- Combat stats can be improved through class progression and upgrades

## Strategic Depth

The Hero Battle system creates meaningful tactical decisions through:
- **Action Sequencing**: Planning multiple turns ahead
- **Resource Management**: Balancing health, focus, and positioning
- **Risk Assessment**: Choosing when to attack, defend, or recover
- **Opponent Analysis**: Adapting strategy based on enemy capabilities
- **Long-term Planning**: Developing heroes to complement dominion strategy

## Integration with Core Gameplay

Hero battles are seamlessly integrated into the broader OpenDominion experience:
- Heroes gain experience from dominion management activities
- Combat effectiveness is enhanced by dominion buildings (shrines)
- Raid encounters contribute to realm-wide objectives
- Tournament victories provide prestige that benefits dominion development
- The system operates on the same time-based mechanics as the main game

This creates a rich sub-game that enhances the overall OpenDominion experience while maintaining the game's focus on strategic depth and competitive multiplayer interaction.

---

# Technical Implementation Guide

This section provides detailed technical documentation for developers and LLMs to understand and extend the hero battle system.

## Core Architecture

### Service Layer
**HeroBattleService** (`src/Services/Dominion/HeroBattleService.php`)
- Primary orchestrator for all battle-related operations
- Handles battle lifecycle: creation, turn processing, and resolution
- Key constants:
  - `DEFAULT_TIME_BANK = 7200` (2 hours in seconds)
  - `DEFAULT_STRATEGY = 'balanced'`

### Helper Classes
**HeroHelper** (`src/Helpers/HeroHelper.php`)
- Provides combat action definitions and strategies
- Manages hero class metadata and upgrade descriptions
- Contains all combat action processors and message templates

**HeroEncounterHelper** (`src/Helpers/HeroEncounterHelper.php`)
- Defines NPC enemies and encounters
- Provides enemy stats and behaviors for PvE battles
- Maps encounters to raid objectives

### Calculator Layer
**HeroCalculator** (`src/Calculators/Dominion/HeroCalculator.php`)
- Handles all combat calculations (damage, evasion, healing)
- Manages experience and level calculations
- Processes ELO-style rating changes
- Key constants:
  - `INACTIVE_CLASS_PENALTY = 0.5` (50% effectiveness)
  - `CLASS_CHANGE_COOLDOWN_HOURS = 72`

## Database Schema

### Core Models

**Hero** (`src/Models/Hero.php`)
```php
// Core attributes
id: int
dominion_id: int
name: string
class: string (hero class key)
experience: int
class_data: array (JSON - stores experience per class)
last_class_change_at: timestamp

// Combat stats
combat_rating: int (ELO-style rating)
stat_combat_wins: int
stat_combat_losses: int
stat_combat_draws: int
```

**HeroBattle** (`src/Models/HeroBattle.php`)
```php
id: int
round_id: int
current_turn: int (starts at 1)
pvp: boolean (true for PvP, false for PvE)
raid_tactic_id: int|null (links to raid encounters)
winner_combatant_id: int|null
finished: boolean
last_processed_at: timestamp
```

**HeroCombatant** (`src/Models/HeroCombatant.php`)
```php
// Identity
id: int
hero_battle_id: int
hero_id: int|null (null for NPCs)
dominion_id: int|null (null for NPCs)
name: string

// Combat Statistics
health: int (maximum health)
attack: int
defense: int
evasion: int (percentage)
focus: int
counter: int
recover: int
shield: int (absorbs damage before health)
current_health: int

// Turn Management
has_focus: boolean (focus state active)
actions: array|null (JSON - queued actions)
current_action: string|null (current turn action)
current_target: int|null (target combatant_id)
last_action: string|null
last_action_at: timestamp

// Automation
time_bank: int (seconds remaining)
automated: boolean (AI-controlled)
strategy: string (strategy key)
abilities: array|null (special abilities)
status: array|null (status effects like 'undying')
```

**HeroBattleAction** (`src/Models/HeroBattleAction.php`)
```php
id: int
hero_battle_id: int
combatant_id: int
target_combatant_id: int|null
turn: int
action: string (action key)
damage: int
health: int (healing or self-damage)
description: string (formatted message)
```

## Combat System Architecture

### Turn Processing Flow

1. **Battle Processing** (`processBattles()`)
   - Called hourly for all active battles
   - Updates time banks based on elapsed time
   - Processes turns when all combatants ready

2. **Turn Resolution** (`processTurn()`)
   ```php
   // Turn execution order:
   1. Verify all living combatants are ready
   2. Determine actions for each combatant (queue or AI)
   3. Execute all actions simultaneously
   4. Apply damage/healing to combatants
   5. Process post-combat effects (abilities like hardiness)
   6. Process status effects (undying, darkness, etc.)
   7. Check win conditions
   8. Increment turn counter or end battle
   ```

3. **Action Determination** (`determineAction()`)
   ```php
   // Priority order:
   1. Check queued actions (player-submitted)
   2. Check passive abilities (darkness, summon_skeleton)
   3. Fall back to strategy-based AI selection
   ```

### Action Processing System

**Action Processors** (methods in HeroBattleService)
All action processors follow signature:
```php
processXAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
// Returns: ['damage' => int, 'health' => int, 'description' => string]
```

Available processors:
- `processAttackAction()` - Standard attack with evasion/counter
- `processDefendAction()` - Doubles defense stat
- `processFocusAction()` - Sets focus state
- `processCounterAction()` - Prepares counter-attack
- `processRecoverAction()` - Heals health
- `processStatAction()` - Modifies combatant stats (shield, attack, defense, etc.)
- `processVolatileAction()` - Risk/reward actions with success chance
- `processFlurryAction()` - Multiple attacks with damage penalty
- `processSummonAction()` - Spawns additional NPC combatants

### Combat Action Definitions

Actions defined in `HeroHelper::getCombatActions()`:
```php
[
    'name' => string,           // Display name
    'processor' => string,      // Processor method name (without 'process' prefix)
    'type' => string,          // 'hostile', 'self', 'passive'
    'limited' => boolean,      // Cannot be used consecutively
    'special' => boolean,      // Requires class/ability
    'class' => string|null,    // Required class
    'attributes' => array,     // Action-specific parameters
    'messages' => array        // Message templates (sprintf format)
]
```

### Combat Calculation Formulas

**Damage Calculation** (`HeroCalculator::calculateCombatDamage()`)
```php
baseDamage = combatant.attack
if (combatant.current_action == 'counter'):
    baseDamage += combatant.counter
else if (combatant.has_focus):
    baseDamage += combatant.focus

baseDefense = target.defense
if (target.current_action == 'recover'):
    baseDefense -= 5
if (target.current_action == 'defend'):
    baseDefense *= 2
    baseDefense += actionDef['attributes']['defend'] // modifier (usually negative)

damage = max(0, baseDamage - baseDefense)
return round(damage)
```

**Evasion Calculation** (`HeroCalculator::calculateCombatEvade()`)
```php
// Some actions bypass evasion (evade = false in actionDef)
if (actionDef['attributes']['evade'] !== null):
    return actionDef['attributes']['evade']

// Roll against evasion percentage
return rand(0, 100) < target.evasion

// If evaded:
damage = damage * 0.5  // Default multiplier
// Special case: 'elusive' ability without focus
if (target has 'elusive' ability AND !combatant.has_focus):
    damage = 0  // Complete negation
```

**Healing Calculation** (`HeroCalculator::calculateCombatHeal()`)
```php
healing = combatant.recover

// Special case: 'mending' ability with focus
if (combatant has 'mending' ability AND combatant.has_focus):
    healing += combatant.focus

return healing
```

**Rating Change (ELO)** (`HeroCalculator::calculateRatingChange()`)
```php
k = 32  // K-factor
expected = 1 / (1 + 10^((opponentRating - currentRating) / 480))
newRating = currentRating + k * (result - expected)
// result: 1 = win, 0 = loss, 1/playerCount = draw
return round(newRating)
```

## Special Abilities System

### Passive Abilities
Checked during combat processing without explicit activation:

**hardiness** (Farmer)
- Prevents death once per battle
- Checked in `processPostCombat()`
- Sets health to 1 instead of 0 or below

**mending** (Healer)
- Enhances recover action with focus stat
- Checked in `processRecoverAction()`
- Does NOT consume focus state

**channeling** (Sorcerer)
- Allows stacking focus (use focus while already focused)
- Checked in `processFocusAction()`
- Adds another focus stat value to existing focus bonus

**last_stand** (Scion)
- Increases all combat stats by 10% when health ≤ 40
- Checked in `getCombatStat()` for all stats

**enrage** (NPC ability)
- Increases attack by 10 when health ≤ 40
- Checked in `getCombatStat()` for attack

**rally** (NPC ability)
- Increases defense by 5 when health ≤ 40
- Checked in `getCombatStat()` for defense

**elusive** (Nightbringer)
- Non-focused attacks deal 0 damage when evaded (instead of 50%)
- Checked in action processors during evasion

**undying** (Various NPCs)
- Returns from death after 5 turns
- Managed via status['undying'] counter in `processStatus()`

**undying_legion** (Eternal Guardian)
- Immune to damage (defense = 999) while minions alive
- Checked in `getCombatStat()` for defense

**dying_light** (Nox Cultist)
- Upon death, reduces Nightbringer's evasion to 0
- Checked in `processPostCombat()`
- Finds Nightbringer by name and permanently sets evasion stat to 0

### Active Abilities
Triggered automatically by AI on specific turns:

**darkness** (Nightbringer)
- Activates every 2nd turn
- Increases evasion by 25
- Processed via `processStatAction()`

**summon_skeleton** (Eternal Guardian)
- Activates every 4th turn
- Spawns a Skeleton Warrior
- Processed via `processSummonAction()`

## Creating New Features

### Adding a New Combat Action

1. **Define the action** in `HeroHelper::getCombatActions()`:
```php
'my_new_action' => [
    'name' => 'Action Name',
    'processor' => 'myNew',  // Will call processMyNewAction()
    'type' => 'hostile',     // or 'self', 'passive'
    'limited' => false,      // true = cannot use consecutively
    'special' => true,       // true = requires class/ability
    'class' => 'my_class',   // optional: restrict to class
    'attributes' => [
        // Custom parameters for your action
        'my_param' => 10,
    ],
    'messages' => [
        'success' => '%s performs amazing action on %s for %s damage!',
        'fail' => '%s attempts action but fails!',
    ]
]
```

2. **Create the processor** in `HeroBattleService`:
```php
public function processMyNewAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
{
    // Your action logic here
    $damage = 0;
    $health = 0;
    $description = '';

    // Example: Use parameters from actionDef
    $myParam = $actionDef['attributes']['my_param'];

    return [
        'damage' => $damage,
        'health' => $health,
        'description' => $description
    ];
}
```

3. **Add to strategies** (if applicable) in `HeroHelper::getCombatStrategies()`:
```php
'my_strategy' => [
    'name' => 'My Strategy',
    'type' => 'npc',  // or 'basic' for player-selectable
    'options' => [
        'attack' => 3,
        'my_new_action' => 5,  // Weight for random selection
        'defend' => 1
    ]
]
```

### Adding a New NPC Enemy

1. **Define enemy stats** in `HeroEncounterHelper::getEnemies()`:
```php
'my_enemy' => [
    'name' => 'My Enemy',
    'health' => 100,
    'attack' => 30,
    'defense' => 15,
    'evasion' => 20,
    'focus' => 10,
    'counter' => 10,
    'recover' => 20,
    'strategy' => 'aggressive',
    'abilities' => ['hardiness', 'enrage'],  // optional
]
```

2. **Create encounter definition** in `HeroEncounterHelper::getEncounters()`:
```php
'my_encounter' => [
    'name' => 'My Encounter',
    'source' => 'Raid (My Raid Name)',
    'enemies' => [
        ['key' => 'my_enemy', 'name' => 'My Enemy #1'],
        ['key' => 'my_enemy', 'name' => 'My Enemy #2'],
    ],
]
```

3. **Link to raid tactic** (if raid encounter):
   - Create raid tactic in database
   - Set `raid_tactic_id` when creating battle via `HeroBattleService::createPracticeBattle()`

### Adding a New Hero Class

1. **Define class** in `HeroHelper::getClasses()`:
```php
[
    'name' => 'My Class',
    'key' => 'my_class',
    'class_type' => 'basic',  // or 'advanced'
    'perk_type' => 'my_perk',  // Passive bonus type
    'coefficient' => 1.0,      // Passive bonus multiplier per level
    'icon' => 'ra-my-icon',
    // For advanced classes:
    'requirement_stat' => 'prestige',
    'requirement_value' => 500,
    'perks' => ['special_perk_1'],  // Additional perks
]
```

2. **Add passive help string** in `HeroHelper::getPassiveHelpString()`:
```php
'my_perk' => '%+.2f%% my custom bonus',
```

3. **Create class-specific action** (optional):
```php
// In getCombatActions()
'my_class_ability' => [
    'name' => 'Class Ability',
    'processor' => 'stat',
    'type' => 'self',
    'limited' => true,
    'special' => true,
    'class' => 'my_class',
    'attributes' => ['stat' => 'attack', 'value' => 5],
    'messages' => ['stat' => '%s gains 5 attack!']
]
```

### Adding a New Passive Ability

1. **Add ability to enemy/upgrade**:
```php
// In HeroEncounterHelper::getEnemies()
'abilities' => ['my_new_ability']
```

2. **Implement ability logic**:

For combat stat modification:
```php
// In HeroCalculator::getCombatStat()
if (in_array('my_new_ability', $combatant->abilities ?? [])) {
    // Modify stat based on conditions
    if ($combatant->current_health <= 50) {
        return round($combatant->{$stat} * 1.2);
    }
}
```

For action processing:
```php
// In HeroBattleService::processAttackAction() or other processor
if (in_array('my_new_ability', $combatant->abilities ?? [])) {
    // Modify damage, healing, etc.
    $damage *= 1.5;
}
```

For status effects:
```php
// In HeroBattleService::processStatus()
if (in_array('my_new_ability', $combatant->abilities ?? [])) {
    $status = $combatant->status ?? [];
    // Update status tracking
    $combatant->update(['status' => $status]);
}
```

For post-combat effects:
```php
// In HeroBattleService::processPostCombat()
if (in_array('my_new_ability', $combatant->abilities ?? []) && condition) {
    $this->spendAbility($combatant, 'my_new_ability');
    return " {$combatant->name} triggers ability!";
}
```

## Key Integration Points

### Experience Gain
Heroes gain experience from dominion activities. To award XP:
```php
$heroCalculator = app(HeroCalculator::class);
$xpGain = $heroCalculator->getExperienceGain($dominion, $baseXP);
$dominion->hero->experience += $xpGain;
$dominion->hero->save();
```

### Raid Integration
Link battles to raid objectives:
```php
$heroBattle = HeroBattle::create([
    'round_id' => $round->id,
    'pvp' => false,
    'raid_tactic_id' => $tacticId
]);
```

On battle completion, `setWinner()` automatically creates `RaidContribution` records.

### Shrine Bonus
Shrines enhance hero effectiveness:
```php
// XP multiplier: 40% per shrine / total land (max 200%)
// Passive bonus multiplier: Same calculation
// Implemented in HeroCalculator::getExperienceMultiplier()
// and getPassiveBonusMultiplier()
```

### Time Bank Management
```php
// Check if combatant has time remaining
$combatant->timeElapsed();  // Seconds since last action
$combatant->timeLeft();     // Remaining time bank

// When time expires:
if ($combatant->time_bank <= 0) {
    $combatant->automated = true;  // Switch to AI control
}
```

## Testing Considerations

When building new features, test:
1. **Action Sequencing**: Limited actions can't be used consecutively
2. **Focus Management**: Focus state consumed correctly, channeling stacks
3. **Damage Calculation**: Defense modifiers, evasion, counter-attacks
4. **Special Abilities**: Passive triggers, one-time effects (hardiness)
5. **Multi-Combatant**: Free-for-all with 3+ combatants
6. **Win Conditions**: Draws, simultaneous death, NPC-only remaining
7. **Time Bank**: Automated switching, time calculations
8. **Rating Changes**: ELO calculations for various scenarios

## File Reference Quick Index

- **Service**: `src/Services/Dominion/HeroBattleService.php:1`
- **Calculator**: `src/Calculators/Dominion/HeroCalculator.php:1`
- **Hero Helper**: `src/Helpers/HeroHelper.php:1`
- **Encounter Helper**: `src/Helpers/HeroEncounterHelper.php:1`
- **Models**: `src/Models/Hero*.php`
- **Actions**: `HeroHelper.php:248` (getCombatActions)
- **Strategies**: `HeroHelper.php:584` (getCombatStrategies)
- **Enemies**: `HeroEncounterHelper.php:37` (getEnemies)
- **Encounters**: `HeroEncounterHelper.php:229` (getEncounters)
- **Classes**: `HeroHelper.php:14` (getClasses)
