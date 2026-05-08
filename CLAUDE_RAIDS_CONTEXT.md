# RAIDS FEATURE CONTEXT

This document provides comprehensive context about the Raids feature in OpenDominion for future Claude Code sessions.

## Feature Overview

The Raids system is a **multiplayer competitive feature** that allows players within realms to work together toward common objectives while competing against other realms. Players can contribute through various tactics (espionage, exploration, investment, magic, invasion, hero battles) to complete time-limited objectives within larger raid campaigns. Each realm competes independently to be the first to complete objectives, with a leaderboard showing inter-realm progress.

## Core Components

### 1. Database Schema

**Primary Tables:**
- `raids` - Main raid campaigns with timing, rewards, and completion bonuses
- `raid_objectives` - Individual objectives within raids with score requirements and timing
- `raid_objective_tactics` - Specific tactics/actions that can be performed for each objective
- `raid_contributions` - Records of individual dominion contributions to objectives
- `hero_battles` - Extended with `raid_tactic_id` for raid-based hero battles

**Key Relationships:**
- Round → Raids (1:many)
- Raid → RaidObjectives (1:many)
- RaidObjective → RaidObjectiveTactics (1:many)
- RaidObjective → RaidContributions (1:many)
- Dominion → RaidContributions (1:many)
- RaidObjectiveTactic → HeroBattles (1:many, optional)

### 2. Model Structure

**Raid Model** (`/src/Models/Raid.php`):
- Properties: name, description, reward_resource, reward_amount, completion_reward_resource, completion_reward_amount, start_date, end_date
- Key methods: `isActive()`, `hasStarted()`, `hasEnded()`, `getStatusAttribute()`, `timeUntilEnd()`, `timeUntilStart()`
- Relationships: `round()`, `objectives()`
- Scopes: `active()` - filters for currently active raids

**RaidObjective Model** (`/src/Models/RaidObjective.php`):
- Properties: name, description, order, score_required, start_date, end_date
- Key methods: Same timing methods as Raid model
- Relationships: `raid()`, `tactics()`
- Scopes: `active()` - filters for currently active objectives

**RaidObjectiveTactic Model** (`/src/Models/RaidObjectiveTactic.php`):
- Properties: type, name, attributes (array), bonuses (array)
- Key methods: `getSortOrderAttribute()` - defines tactic display order
- Relationships: `objective()`
- Tactic types: 'hero', 'investment', 'exploration', 'espionage', 'magic', 'invasion'

**RaidContribution Model** (`/src/Models/RaidContribution.php`):
- Properties: realm_id, dominion_id, raid_objective_id, type, score
- Relationships: `dominion()`, `objective()`, `realm()`
- Tracks individual contributions to objectives

### 3. Service Layer

**RaidActionService** (`/src/Services/Dominion/Actions/RaidActionService.php`):
- Main service for processing raid actions
- Methods:
  - `performAction()` - Entry point for all raid actions
  - `processAction()` - Handles basic actions (espionage, exploration, investment, magic)
  - `processHeroBattle()` - Handles hero battle tactics
  - `processInvasion()` - Handles invasion tactics with casualties
  - `calculateCosts()` - Calculates resource costs for tactics
  - `validateInvasionRequirements()` - Validates invasion requirements
- Integrates with: InvasionService, HeroBattleService, HistoryService, QueueService

### 4. Calculator Layer

**RaidCalculator** (`/src/Calculators/RaidCalculator.php`):
- **Core Methods** (unified API with optional realm parameter):
  - `getTacticManaCost()` - Calculates mana cost for magic tactics
  - `getTacticPointsEarned()` - Calculates actual points earned with multipliers
  - `getObjectiveScore(?Realm $realm = null)` - Gets score for objective (all realms if null, specific realm if provided)
  - `getObjectiveProgress(?Realm $realm = null)` - Gets completion percentage (all realms if null, specific realm if provided)
  - `isObjectiveCompleted(?Realm $realm = null)` - Checks if objective is completed (any realm if null, specific realm if provided)
- **Contribution Methods**:
  - `getDominionContribution()` - Gets dominion's contribution to objective
  - `getRealmContribution()` - Gets realm's contribution to objective
  - `getDominionContributionPercentage()` - Gets dominion's contribution percentage
  - `getRealmContributionPercentage()` - Gets realm's contribution percentage
- **Display Methods**:
  - `getRecentContributions()` - Gets recent contributions for display (all realms)
  - `getRecentContributionsInRealm()` - Gets recent contributions within a specific realm
  - `getTopContributors()` - Gets top contributors leaderboard (all realms)
  - `getTopContributorsInRealm()` - Gets top contributors within a specific realm
- **Leaderboard Methods**:
  - `getRealmsLeaderboard()` - Gets realm leaderboard for an objective
  - `getCompletedRealms()` - Gets realms that have completed the objective
- **Backward Compatibility Aliases**:
  - `getRealmObjectiveScore()` - Alias for `getObjectiveScore($objective, $realm)`
  - `getRealmObjectiveProgress()` - Alias for `getObjectiveProgress($objective, $realm)`
  - `isRealmObjectiveCompleted()` - Alias for `isObjectiveCompleted($objective, $realm)`

### 5. Helper Layer

**RaidHelper** (`/src/Helpers/RaidHelper.php`):
- `getTypes()` - Returns all tactic types
- `getTacticAttributeSchema()` - Returns attribute schema for each tactic type
- `getTacticBonusSchema()` - Returns bonus schema for tactics

## Tactic Types

### 1. Espionage
- **Cost**: spy_strength
- **Attributes**: strength_cost, points_awarded
- **Multipliers**: Uses espionage score multiplier from OpsCalculator
- **Bonuses**: Race (halfling, elf), Tech (spy_networks)

### 2. Exploration
- **Cost**: military_draftees, morale
- **Attributes**: draftee_cost, morale_cost, points_awarded
- **Bonuses**: Race (dwarf, human), Tech (engineering)

### 3. Investment
- **Cost**: Various resources (platinum, lumber, ore, gems, food)
- **Attributes**: resource, amount, points_awarded
- **Bonuses**: Race (gnome, dwarf, human), Tech (economics, construction)

### 4. Magic
- **Cost**: resource_mana (calculated), wizard_strength
- **Attributes**: mana_cost (multiplier), strength_cost, points_awarded
- **Multipliers**: Uses magic score multiplier from OpsCalculator
- **Bonuses**: Race (elf, lizardfolk), Tech (magical_focus)

### 5. Invasion
- **Cost**: military units, morale
- **Attributes**: casualties (percentage), target_type
- **Points**: Calculated dynamically based on damage dealt
- **Bonuses**: Race (human, dwarf, elf), Tech (military_tactics, archery)
- **Special**: Uses invasion service for validation and processing

### 6. Hero
- **Cost**: Hero availability
- **Attributes**: NPC combat stats (health, attack, defense, etc.), points_awarded
- **Bonuses**: Race, Hero class, Tech
- **Special**: Creates hero battles, one-time completion per dominion

## User Interface

### Controller Layer
**RaidController** (`/src/Http/Controllers/Dominion/RaidController.php`):
- Routes:
  - `GET /raids` - Main raids listing (realm-specific progress)
  - `GET /raids/objective/{objective}` - Individual objective page (realm-specific data)
  - `GET /raids/objective/{objective}/leaderboard` - Realm leaderboard for objective
  - `POST /raids/tactic/{tactic}` - Submit tactic action

### View Structure
**Main Pages**:
- `raids.blade.php` - Lists all raids with realm-specific progress indicators
- `raid-objective.blade.php` - Shows objective details with realm-specific progress bars
- `raid-leaderboard.blade.php` - Shows realm leaderboard for an objective
- `raid_attacked.blade.php` - Shows invasion results

**Tactic Partials**:
- `espionage.blade.php` - Espionage operations form
- `exploration.blade.php` - Exploration form
- `investment.blade.php` - Resource investment form
- `magic.blade.php` - Magic spell form
- `invasion.blade.php` - Military invasion form
- `hero.blade.php` - Hero battle form

## Testing

### Test Coverage
- `RaidCalculatorTest.php` - Unit tests for RaidCalculator
- `RaidActionServiceTest.php` - Unit tests for RaidActionService
- Database seeders with comprehensive test data

### Development Commands
```bash
# Run raid-specific tests
./vendor/bin/phpunit tests/Unit/Calculators/RaidCalculatorTest.php
./vendor/bin/phpunit tests/Unit/Services/Action/RaidActionServiceTest.php

# Seed test data
php artisan db:seed --class=RaidSeeder
```

## Data Flow

1. **Raid Creation**: Raids are created via seeder or admin interface
2. **Objective Setup**: Each raid has multiple objectives with different timing
3. **Tactic Configuration**: Each objective has multiple tactics with different costs/rewards
4. **Player Action**: Player selects tactic and submits action
5. **Validation**: RaidActionService validates requirements and costs
6. **Processing**: Action is processed, resources deducted, contribution recorded
7. **Progress Tracking**: RaidCalculator tracks realm-specific progress and completion
8. **Leaderboard**: Real-time leaderboard shows inter-realm competition
9. **Rewards**: Rewards distributed when objectives/raids complete per realm

## Integration Points

### History Service
- Events: `EVENT_ACTION_RAID_ACTION`, `EVENT_ACTION_RAID_ATTACKED`
- Tracks all raid actions in dominion history

### Queue Service
- Handles returning units from invasion tactics
- Manages boat usage for invasion tactics

### Game Events
- `raid_attacked` events for invasion tactics
- Displays attack results and damage dealt

### Hero System
- Creates hero battles for hero tactics
- Links battles to raid tactics via `raid_tactic_id`

## Security Considerations

### Validation
- Time-based validation for active objectives
- Resource availability checks
- Invasion requirement validation (40% rule, 5:4 rule, morale, boats)
- Protection from multiple simultaneous actions

### Guards
- Uses `DominionGuardsTrait` for locked dominion checks
- Prevents actions during tick processing
- Respects offensive action disables

## Configuration

### Tactic Attributes Schema
Each tactic type has a specific attribute schema defined in RaidHelper:
- **Espionage**: strength_cost, points_awarded
- **Exploration**: draftee_cost, morale_cost, points_awarded
- **Investment**: resource, amount, points_awarded
- **Magic**: mana_cost, strength_cost, points_awarded
- **Invasion**: casualties (percentage)
- **Hero**: NPC combat stats, points_awarded

### Bonus System
Bonuses can be applied based on:
- Race (e.g., halfling bonus for espionage)
- Technology (e.g., spy_networks bonus)
- Hero class (e.g., blacksmith bonus for hero tactics)
- Unit type (for invasion tactics)
- Alignment (for various tactics)

## Performance Considerations

### Database Optimization
- Indexes on raid_objective_id, dominion_id, realm_id for contributions
- Efficient queries for progress calculation
- Proper eager loading in views

### Caching Opportunities
- Objective progress calculations
- Contribution leaderboards
- Total scores (currently calculated real-time)

## Future Enhancements

### Potential Improvements
- Raid rewards distribution system
- Advanced bonus mechanics
- Raid-specific achievements
- Real-time progress updates
- Raid analytics and reporting

### Technical Debt
- TODOs in models for attribute/bonus improvements
- Refactoring attack result handling
- Improving tactic bonus schema flexibility

## Recent Updates

### Realm-Specific Competition (Current Implementation)
- **Architecture Change**: Converted from global cooperative objectives to realm-vs-realm competition
- **Scoring**: Each realm competes independently to reach the score requirement first
- **Progress Tracking**: Progress bars show realm-specific completion (0-100%)
- **Leaderboard**: New leaderboard page shows all realms' progress and rankings
- **UI Updates**: All views updated to emphasize realm competition over global cooperation

### Key Changes Made:
1. **RaidCalculator**: Added realm-specific methods for scoring and progress
2. **Views**: Updated to show realm progress vs. global progress
3. **Leaderboard**: New dedicated leaderboard page with realm rankings
4. **Navigation**: Updated to highlight active raids section

This context document should provide comprehensive understanding of the Raids feature for future development sessions.