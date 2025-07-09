# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

OpenDominion is a PHP Laravel 8.x application - a free and open-source clone of the original Dominion browser-based fantasy war game. It's a persistent browser-based game (PBBG) where players manage dominions in realms and compete through military, magic, and espionage.

## Architecture

**Custom Laravel Structure**: The application uses a non-standard Laravel structure with source code in `/src/` instead of `/app/`:

- `/src/` - Main application code (custom namespace: `OpenDominion\`)
- `/app/` - Laravel resources (config, database, views, routes)
- PSR-4 autoloading: `"OpenDominion\\": "src/"`

**Key Directories:**
- `/src/Models/` - Eloquent models (User, Dominion, Realm, etc.)
- `/src/Services/` - Business logic services
- `/src/Calculators/` - Game mechanics calculations
- `/src/Helpers/` - Domain utility classes
- `/src/Http/Controllers/` - Application controllers
- `/app/database/migrations/` - 120+ migration files
- `/tests/` - Comprehensive test suite (Feature, Unit, Http)

## Development Commands

**Environment Setup:**
```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
```

**Development Server:**
```bash
php artisan serve  # Starts server at http://localhost:8000
```

**Asset Compilation:**
```bash
npm run dev        # Development build
npm run watch      # Watch for changes
npm run prod       # Production build
```

**Testing:**
```bash
./vendor/bin/phpunit                    # Run all tests
./vendor/bin/phpunit --testsuite=Unit   # Run unit tests only
./vendor/bin/phpunit --testsuite=Feature # Run feature tests only
```

**Game-Specific Commands:**
```bash
php artisan game:tick                   # Runs game tick (hourly scheduled)
php artisan game:data:sync             # Syncs game data
php artisan dev:seed:realms --count=20 # Creates test realms for development
```

**Code Quality:**
```bash
php artisan ide-helper:generate        # Generate IDE helpers (auto-runs on composer update)
```

## Game Domain Structure

**Core Entities:**
- **User** - Player accounts with authentication
- **Round** - Game rounds with specific rules and timeframes  
- **Realm** - Groups of players (12-15 dominions) working together
- **Dominion** - Individual player's game state
- **Race** - Fantasy races with unique abilities and units

**Game Systems:**
- **Military** - Unit training, invasion mechanics (`/src/Services/Actions/`)
- **Magic** - Spell casting, mana management (`/src/Calculators/`)
- **Espionage** - Information gathering, black ops
- **Construction** - Building management and effects
- **Heroes** - Character progression system
- **Technology** - Research tree advancement
- **Wonders** - Realm-level collaborative objectives

## Database

- **MySQL/MariaDB** with extensive schema (120+ migrations)
- **Queue system** for timed actions and game events
- **Comprehensive relationships** between all game entities
- Use `php artisan migrate:fresh --seed` for clean database setup

## Testing Strategy

- **Unit tests** for calculators and core business logic
- **Feature tests** for game mechanics and user interactions  
- **HTTP tests** for controller endpoints
- Custom test traits for data creation in `/tests/Traits/`
- Always run tests before committing changes

## Key Files for Development

**Entry Points:**
- `/src/Application.php` - Custom Laravel application class
- `/app/routes/web.php` - Route definitions
- `/src/Http/Kernel.php` - Middleware configuration

**Core Models:**
- `/src/Models/Dominion.php` - Main game entity
- `/src/Models/User.php` - Player accounts
- `/src/Models/Realm.php` - Player groups

**Business Logic:**
- `/src/Services/` - All business logic services
- `/src/Calculators/` - Game mechanics calculations
- `/src/Helpers/` - Domain-specific utilities

## Code Conventions

- Follow **PSR-2** coding standards (StyleCI configured)
- Use **Service classes** for business logic, not controllers
- **Calculator classes** for complex game mechanics
- **Helper classes** for domain utilities
- Comprehensive **type hints** and **docblocks**
- Prefer **explicit over implicit** - clarity over brevity

## Development Workflow

1. **Feature branches** from `develop` branch
2. Run `./vendor/bin/phpunit` before committing
3. Use `php artisan dev:seed:realms` to create test data
4. Game ticks run hourly - test time-based functionality carefully
5. Check both game mechanics and UI when making changes

## Special Considerations

- **Game state consistency** is critical - use database transactions
- **Real-time elements** - some actions are queued and processed hourly
- **Complex calculations** - many interconnected formulas in Calculators
- **Multiplayer dynamics** - changes affect entire realms/rounds
- **Game balance** - modifications to mechanics require careful testing