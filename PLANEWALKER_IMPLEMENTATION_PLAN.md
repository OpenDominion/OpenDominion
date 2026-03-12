# Planewalker Race Implementation Plan

## Overview
Implement the Planewalker race with unique mechanics:
- Cannot build homes or barracks
- No population growth (military only - no peasants/birth mechanics)
- Passive unit summoning based on Conjurer/Summoner counts
- Unit ownership caps (max 3 summoned units per Conjurer/Summoner)

## User Requirements Clarified
- **Summoning cap**: Total units owned capped at 3 per summoner (e.g., 100 Conjurers = max 300 Golems total)
- **Population**: No population growth, military only (race cannot sustain peasants)
- **Unit costs**: 1200 platinum for Conjurer and Summoner
- **Race details**: Neutral alignment, Cavern home land, wizard_power +10% only racial perk

## Critical Files to Modify

### 1. Race Definition
**File**: `/app/data/races/planewalker.yml` (CREATE NEW)
- Define race with neutral alignment, cavern home land
- Only racial perk: `wizard_power: 10`
- Define 4 units with summoning perks and training restrictions

### 2. Building Restrictions
**File**: `/src/Services/Dominion/Actions/ConstructActionService.php`
- Add validation in `construct()` method to prevent building restricted buildings
- Check race key and throw GameException if attempting to build homes/barracks

**File**: `/app/resources/views/pages/dominion/construction.blade.php`
- Show home and barracks but with disabled input fields for Planewalker
- Add visual indicator (grayed out, tooltip explaining restriction)

### 3. Unit Training Restrictions
**File**: `/src/Calculators/Dominion/Actions/TrainingCalculator.php`
- Modify `getMaxTrainable()` to return 0 for units with `cannot_train` perk
- Check unit perks for Elemental and Golem

**File**: `/src/Services/Dominion/Actions/Military/TrainActionService.php`
- Add validation in `train()` to prevent training units with `cannot_train` perk
- Throw GameException if attempt made

**File**: `/app/resources/views/pages/dominion/military.blade.php`
- Update UI to show "Summoned only" message for units with `cannot_train` perk
- Disable training input fields for those units

### 4. Passive Summoning System
**File**: `/src/Services/Dominion/TickService.php`
- Add new Planewalker summoning logic in `performSpellEffects()` method (after line 817)
- Calculate summoned units based on Conjurer/Summoner counts
- Apply ownership cap: max summoned units = summoner count × 3
- Consume draftees (1 per summoned unit)
- Queue units with 12-hour arrival time

## Implementation Steps

### Step 1: Create Race YAML Definition
Create `/app/data/races/planewalker.yml`:

```yaml
key: planewalker
name: Planewalker
alignment: neutral
description: >-
  <p>Planewalkers are enigmatic beings who traverse the boundaries between worlds, commanding elemental forces without the need for traditional civilization.</p>
  <p>Unable to sustain mortal populations or construct homes, Planewalkers rely entirely on their Conjurers and Summoners to call forth Golems and Elementals from other planes of existence.</p>
attacker_difficulty: 3
explorer_difficulty: 2
converter_difficulty: 3
home_land_type: cavern
perks:
  wizard_power: 10
units:
  - name: Elemental
    cost:
      platinum: 0
      ore: 0
    power:
      offense: 6
      defense: 0
    need_boat: false
    perks:
      cannot_train: 1
  - name: Golem
    cost:
      platinum: 0
      ore: 0
    power:
      offense: 0
      defense: 6
    perks:
      cannot_train: 1
  - name: Conjurer
    cost:
      platinum: 1200
      ore: 0
    power:
      offense: 0
      defense: 1
    perks:
      summons_unit: 2,0.05,3
  - name: Summoner
    cost:
      platinum: 1200
      ore: 0
    power:
      offense: 1
      defense: 0
    perks:
      summons_unit: 1,0.05,3
```

**Notes**:
- `summons_unit: 2,0.05,3` = summons unit slot 2 (Golem), 0.05 rate (1 per 20), max 3 per Conjurer
- `summons_unit: 1,0.05,3` = summons unit slot 1 (Elemental), 0.05 rate (1 per 20), max 3 per Summoner
- Format: `slot,rate,cap` (comma-separated, parsed like Vampire's `conversion` perk)
- `wizard_power: 10` = +10% wizard power (only racial bonus perk)
- `need_boat: false` on Elemental for "no boats needed"
- Population growth naturally constrained by inability to build homes

### Step 2: Implement Building Restrictions

**Add validation** in `/src/Services/Dominion/Actions/ConstructActionService.php::construct()` (after line 86):

```php
// Validate building restrictions for Planewalker
if ($dominion->race->key === 'planewalker') {
    if ($buildingType === 'home') {
        throw new GameException('Your race cannot build homes.');
    }
    if ($buildingType === 'barracks') {
        throw new GameException('Your race cannot build barracks.');
    }
}
```

**Update UI** in `/app/resources/views/pages/dominion/construction.blade.php`:

Find the building construction form fields and add disability check:
```blade
@foreach($buildingHelper->getBuildingTypesByLandType($landType) as $buildingType)
    @php
        $cannotBuild = false;
        if ($selectedDominion->race->key === 'planewalker') {
            if (in_array($buildingType, ['home', 'barracks'])) {
                $cannotBuild = true;
            }
        }
    @endphp

    <input type="number"
           name="construct[{{ $buildingType }}]"
           @if($cannotBuild) disabled @endif
           class="form-control @if($cannotBuild) text-muted @endif">

    @if($cannotBuild)
        <small class="text-muted">Your race cannot build this</small>
    @endif
@endforeach
```

### Step 3: Implement Training Restrictions

**Modify** `/src/Calculators/Dominion/Actions/TrainingCalculator.php::getMaxTrainable()`:

Add check before calculating trainable amounts:
```php
foreach ($costsPerUnit as $unitType => $costs) {
    // Check if unit can be trained
    if (str_starts_with($unitType, 'unit')) {
        $unitSlot = (int)str_replace('unit', '', $unitType);
        $unit = $dominion->race->units->firstWhere('slot', $unitSlot);
        if ($unit && $unit->getPerkValue('cannot_train')) {
            $trainable[$unitType] = 0;
            continue;
        }
    }

    // ... existing logic ...
}
```

**Add validation** in `/src/Services/Dominion/Actions/Military/TrainActionService.php::train()` (in foreach loop after line 78):

```php
// Check if unit can be trained
if (str_starts_with($unitType, 'unit')) {
    $unitSlot = (int)str_replace('unit', '', $unitType);
    $unit = $dominion->race->units->firstWhere('slot', $unitSlot);
    if ($unit && $unit->getPerkValue('cannot_train')) {
        throw new GameException("The {$unit->name} unit cannot be trained manually.");
    }
}
```

**Update UI** in `/app/resources/views/pages/dominion/military.blade.php`:

In unit training section (around line 46-104), disable input instead of hiding:
```blade
@if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
    @php
        $unit = $selectedDominion->race->units->firstWhere('slot', (int)str_replace('unit', '', $unitType));
        $cannotTrain = $unit && $unit->getPerkValue('cannot_train');
    @endphp

    <td class="text-center">
        <div class="input-group">
            <input type="number"
                   name="train[military_{{ $unitType }}]"
                   @if($cannotTrain) disabled @endif
                   class="form-control @if($cannotTrain) text-muted @endif">
            <!-- ... rest of input group ... -->
        </div>
        @if ($cannotTrain)
            <small class="text-muted">Summoned only</small>
        @endif
    </td>
@endif
```

### Step 4: Implement Passive Summoning

**Add to** `/src/Services/Dominion/TickService.php::performSpellEffects()` (after line 817):

```php
// Planewalker passive summoning (unit-based, consumes draftees)
$planewalkerRace = Race::where('key', 'planewalker')->first();
if ($planewalkerRace !== null) {
    $dominions = Dominion::whereIn('id', $dominionIds)
        ->where('race_id', $planewalkerRace->id)
        ->get();

    foreach ($dominions as $dominion) {
        $summonedUnits = [];
        $totalDrafteesNeeded = 0;

        // Process each unit slot for summoning perks
        foreach ($dominion->race->units as $unit) {
            $summonsPerk = $unit->getPerkValue('summons_unit');
            if (!$summonsPerk) {
                continue;
            }

            // Parse comma-separated perk value: slot,rate,cap
            $perkValues = is_array($summonsPerk) ? $summonsPerk : explode(',', $summonsPerk);
            if (count($perkValues) < 3) {
                continue;
            }

            [$targetSlot, $summonRate, $capPerSummoner] = $perkValues;
            $targetSlot = (int)$targetSlot;
            $summonRate = (float)$summonRate;
            $capPerSummoner = (int)$capPerSummoner;

            // Get summoner count (units in this slot)
            $summonerCount = $dominion->{"military_unit{$unit->slot}"};
            if ($summonerCount == 0) {
                continue;
            }

            // Get current summoned unit count (target slot)
            $currentSummonedCount = $this->militaryCalculator->getTotalUnitsForSlot($dominion, $targetSlot);

            // Calculate max allowed (cap × summoner count)
            $maxAllowed = $summonerCount * $capPerSummoner;

            // Calculate how many to summon this hour
            $summonThisHour = floor($summonerCount * $summonRate);

            // Don't exceed cap
            $roomForMore = max(0, $maxAllowed - $currentSummonedCount);
            $actualSummon = min($summonThisHour, $roomForMore);

            if ($actualSummon > 0) {
                $summonedUnits["military_unit{$targetSlot}"] =
                    ($summonedUnits["military_unit{$targetSlot}"] ?? 0) + $actualSummon;
                $totalDrafteesNeeded += $actualSummon;
            }
        }

        // Check if enough draftees available
        if ($totalDrafteesNeeded > 0) {
            $availableDraftees = $dominion->military_draftees;

            if ($totalDrafteesNeeded <= $availableDraftees) {
                // Consume draftees
                $dominion->military_draftees -= $totalDrafteesNeeded;
                $dominion->save();

                // Queue summoned units (12-hour arrival)
                $this->queueService->queueResources('training', $dominion, $summonedUnits, 12);
            } else {
                // Not enough draftees - scale down proportionally
                $scaleFactor = $availableDraftees / $totalDrafteesNeeded;
                $scaledUnits = [];

                foreach ($summonedUnits as $unitType => $count) {
                    $scaledCount = floor($count * $scaleFactor);
                    if ($scaledCount > 0) {
                        $scaledUnits[$unitType] = $scaledCount;
                    }
                }

                if (!empty($scaledUnits)) {
                    // Consume all available draftees
                    $dominion->military_draftees = 0;
                    $dominion->save();

                    // Queue scaled summoned units
                    $this->queueService->queueResources('training', $dominion, $scaledUnits, 12);
                }
            }
        }
    }
}
```

**Logic**:
- For each Planewalker dominion
- Check each unit slot for `summons_unit` perk
- **Parse comma-separated value**: `slot,rate,cap` (e.g., `2,0.05,3`)
  - Uses same pattern as Vampire's `conversion` perk and Wood Elf's land-based perks
  - Perk is auto-parsed into array by `getPerkValue()` if it contains commas
- Calculate: `summonThisHour = floor(summonerCount × rate)` (e.g., 100 Conjurers × 0.05 = 5 Golems/hour)
- Calculate max allowed: `maxAllowed = summonerCount × cap` (e.g., 100 Conjurers × 3 = 300 max Golems)
- Check current count: `currentSummonedCount = getTotalUnitsForSlot()`
- Only summon if below cap: `actualSummon = min(summonThisHour, maxAllowed - currentSummonedCount)`
- **Check available draftees**: Each summoned unit costs 1 draftee
- **Consume draftees**: Deduct from `military_draftees` before queueing
- **Scale if insufficient**: If not enough draftees, scale down proportionally
- Queue with 12-hour training time

### Step 5: Data Sync and Testing

1. **Sync race data**: `php artisan game:data:sync`
   - Verify Planewalker race created in database
   - Check units table for 4 Planewalker units
   - Verify new perk types created: `cannot_train`, `summons_unit`

2. **Test building restrictions**:
   - Create Planewalker dominion
   - Visit construction page - verify home and barracks are disabled
   - Attempt direct construction via form - verify validation blocks it

3. **Test training restrictions**:
   - Visit military page
   - Verify Elemental and Golem show "Summoned only" and disabled input
   - Attempt to train via form - verify validation blocks it

4. **Test passive summoning**:
   - Train some Conjurers and Summoners
   - Ensure draftees are available
   - Run `php artisan game:tick`
   - Check training queue for summoned units
   - Verify cap logic (train 60 Conjurers, verify max 180 Golems eventually)
   - Verify draftee consumption

## Critical Considerations

### Population Mechanics
Without homes:
- Planewalker cannot build homes (30 housing each)
- All other buildings provide 15 housing (standard)
- Population growth naturally limited by reduced housing capacity
- Peasants work in buildings for resource production (normal)
- Peasants can be drafted to draftees (hourly cap based on population)
- Summoned units require draftees to function
- **Key constraint**: Housing-limited population that cannot expand as quickly as other races

This creates a strategic trade-off: limited population must be allocated between:
- Working in resource buildings (production)
- Draftees for passive unit summoning
- Maximum population capped by (number of buildings × 15) instead of having homes

### Housing and Buildings
Without homes and barracks:
- All other buildings provide 15 population housing (standard)
- No home building (30 housing each) means less housing efficiency
- No barracks means no military-specific housing (36 troops per barracks)
- Military units housed within general population cap
- **This is intentional**: Forces reliance on summoned units rather than traditional military

## New Perk Types Required

These will be auto-created by DataSyncCommand when syncing the YAML:

**Unit Perks**:
- `cannot_train`: Prevents manual training of unit (value: 1)
- `summons_unit`: Passive summoning with comma-separated values (format: `slot,rate,cap`)
  - Example: `summons_unit: 2,0.05,3` means "summons unit slot 2 at 0.05 rate (1 per 20 units), max 3 per summoner"
  - Pattern matches Vampire's `conversion: 3,28` and Wood Elf's `defense_from_land: forest,20,2`
  - Automatically parsed into array by `Race::getPerkValue()` method (see Race.php:108-110)

## Summary of Changes

1. **New file**: `planewalker.yml` race definition
2. **ConstructActionService**: Validate building restrictions (server-side)
3. **Construction view**: Disable input fields for restricted buildings
4. **TrainingCalculator**: Return 0 trainable for cannot_train units
5. **TrainActionService**: Block training of cannot_train units
6. **Military view**: Disable input fields for cannot_train units
7. **TickService**: Add Planewalker passive summoning with:
   - Ownership caps (max 3 per summoner)
   - Draftee consumption (1 per summoned unit)
   - Proportional scaling if insufficient draftees

## Next Steps After Implementation

1. Run `php artisan game:data:sync` to load the race
2. Balance testing - verify summoning rates and caps work correctly
3. Test all restrictions (building, training, summoning)
4. Verify draftee consumption mechanics
5. Consider UI tooltips/help text explaining unique mechanics
