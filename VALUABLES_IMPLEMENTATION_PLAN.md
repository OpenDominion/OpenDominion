# Valuables Feature — Implementation Plan

## Codebase conventions to follow

- Models extend `AbstractModel`, live in `src/Models/`
- Business logic in `src/Services/Dominion/` or `src/Services/`
- Constants/lookups in `src/Helpers/`
- Controllers in `src/Http/Controllers/Dominion/`
- Views in `app/resources/views/pages/dominion/`
- Migrations in `app/database/migrations/`
- Routes in `app/routes/web.php`
- History events are string constants on `HistoryService`
- Notifications use `NotificationService::queueNotification()->sendNotifications()`
- Tick processing is in `TickService::precalculateTick()` and the main `performTick()` loop
- Spy strength regen base is `4.0` from `MilitaryCalculator::getSpyStrengthRegen()` (can be >4 with bonuses)

---

## Phase 1 — Data model, tunables, and name generator

### 1.1 Migration

Create `app/database/migrations/<timestamp>_create_valuables_table.php`:

```
valuables
  id                          bigint PK
  round_id                    FK → rounds
  source_dominion_id           FK → dominions   (discoverer / current source)
  target_dominion_id          FK → dominions   (where the item lives)
  name                        varchar
  rarity                      string (enum): common, uncommon, rare, epic, legendary
  type                        string (enum): relic, jewelry, artwork, equipment, text
  status                      string (enum): discovered, investigating, stolen, sold,
                                    listed_for_transfer, transferred, expired, failed
  required_spy_hours          int|null          (frozen at investigation start)
  spies_assigned              int|null          (committed spy count)
  investigation_started_at    timestamp|null
  investigation_ends_at       timestamp|null
  stolen_at                   timestamp|null
  discovered_at               timestamp         (original discovery time — never reset)
  transfer_price              int               (rarity-based, set at discovery)
  is_listed                   boolean default false
  sold_price                  int|null
  transferred                 boolean default false  (ever changed hands)

  timestamps (created_at / updated_at)
  index: [source_dominion_id, status]
  index: [round_id, status]
  index: [target_dominion_id]
```

**Edge case:** `discovered_at` is set once at creation and never updated on transfer. `transferred` flag is set true on first purchase.

### 1.2 Model — `src/Models/Valuable.php`

```php
namespace OpenDominion\Models;

class Valuable extends AbstractModel
{
    protected $casts = [
        'is_listed'                => 'boolean',
        'transferred'              => 'boolean',
        'investigation_started_at' => 'datetime',
        'investigation_ends_at'    => 'datetime',
        'stolen_at'                => 'datetime',
        'discovered_at'            => 'datetime',
    ];

    // Relationships
    public function round()          { return $this->belongsTo(Round::class); }
    public function sourceDominion()  { return $this->belongsTo(Dominion::class, 'source_dominion_id'); }
    public function targetDominion() { return $this->belongsTo(Dominion::class, 'target_dominion_id'); }

    // Scopes
    public function scopeActive(Builder $q)              { return $q->whereNotIn('status', ['sold','expired','failed']); }
    public function scopeForRound(Builder $q, Round $r)  { return $q->where('round_id', $r->id); }
    public function scopeInvestigating(Builder $q)       { return $q->where('status', 'investigating'); }
    public function scopeListed(Builder $q)              { return $q->where('is_listed', true); }

    // Helpers
    public function isActiveInvestigation(): bool { return $this->status === 'investigating'; }
    public function hoursOld(): float             { return $this->discovered_at->diffInSeconds(now()) / 3600; }
}
```

### 1.3 JSON word-pool data file — `app/resources/data/valuables-names.json`

Organize as:

```json
{
  "types": {
    "relic":     { "adjectives": [...], "materials": [...], "bases": [...], "places": [...], "events": [...], "possessives": [...], "epic": [...], "legendary": [...] },
    "jewelry":   { ... },
    "artwork":   { ... },
    "equipment": { ... },
    "text":      { ... }
  }
}
```

Each word list should have at least 12–20 entries. Epic/legendary lists are flat name arrays (e.g., `"The Shattered Crown of Arendmoor"`).

### 1.4 Helper — `src/Helpers/ValuablesHelper.php`

Centralizes all tunables and generation logic:

```php
class ValuablesHelper
{
    // §9 tunables
    public const PASSIVE_DISCOVERY_CHANCE       = 0.01;
    public const SCOUT_DISCOVERY_CHANCE         = 0.10;
    public const SCOUT_SPY_STRENGTH_COST        = 5.0;
    public const EXPIRATION_HOURS               = 48;
    public const MIN_INVESTIGATION_HOURS        = 36;   // slowest option
    public const MAX_INVESTIGATION_HOURS        = 6;    // fastest option
    public const INVESTIGATION_HOUR_STEP        = 6;
    public const SPY_STRENGTH_PER_INVESTIGATION = 2.0;

    // Rarity definitions
    public static function getRarityConfig(): array { ... }
    // returns indexed by rarity key:
    //   spy_hours_multiplier, base_value_min, base_value_max, transfer_price

    // Name generation
    public function generateName(string $type, string $rarity): string { ... }
    // Loads JSON, selects patterns per rarity, fills slots

    // Rarity selection
    public function selectRarity(Dominion $attacker, Dominion $target): string { ... }
    // Land score + spy score → averaged → round(score*4) → rarity key

    // Discovery display string
    public function discoveryPhrase(string $rarity, string $type): string { ... }
    // "a Rare relic", "a Legendary work of art", etc.

    // Current sale price (decays from max to min over 48h after stolen_at)
    public function getCurrentSalePrice(Valuable $valuable): int { ... }

    // Required spy-hours computed from target land at investigation start:
    //   target_land * rarity_config[rarity]['spy_hours_multiplier']
}
```

**Rarity selection implementation:**

```php
$landScore = clamp(($targetLand - 500) / 7500, 0, 1);
$spyScore  = clamp($this->militaryCalculator->getSpyRatioRaw($attacker), 0, 1);
$score     = ($landScore + $spyScore) / 2;
$rarityIndex = (int) round($score * 4);
// map 0→common, 1→uncommon, 2→rare, 3→epic, 4→legendary
```

**Sale price decay:**

```php
$elapsedHours = $valuable->stolen_at->diffInSeconds(now()) / 3600;
$t = min($elapsedHours / self::EXPIRATION_HOURS, 1.0);  // 0..1
$config = self::getRarityConfig()[$valuable->rarity];
return (int) round($config['base_value_max'] - ($config['base_value_max'] - $config['base_value_min']) * $t);
```

---

## Phase 2 — Discovery hooks

### 2.1 Passive discovery in `EspionageActionService::performInfoGatheringOperation()`

After `$infoOp->save()` succeeds (line ~407 currently), add:

```php
$discoveryMessage = $this->valuablesService->attemptPassiveDiscovery($dominion, $target);
// Returns '' or " Your spies have discovered a Rare relic in the target's possession!"
```

Return value is appended to `'message'` in the returned array.

### 2.2 Passive discovery in `SpellActionService` info op path

Same hook after `$infoOp->save()` in `SpellActionService`. The spell equivalent:

```php
$discoveryMessage = $this->valuablesService->attemptPassiveDiscovery($dominion, $target, 'wizards');
// " Your wizards have also discovered a Common text in the target's possession!"
```

`ValuablesService::attemptPassiveDiscovery()` accepts a `string $agent = 'spies'` parameter to vary the phrasing.

### 2.3 Scout for Valuables operation in `EspionageHelper`

Add a new method `getValuablesOperations()` returned by `getOperations()`, with a single entry:

```php
[
    'name'        => 'Scout for Valuables',
    'description' => 'Search the target for hidden treasures (10% chance)',
    'key'         => 'scout_for_valuables',
]
```

Add `isValuablesOperation(string $key): bool` helper method.

### 2.4 Handle `scout_for_valuables` in `EspionageActionService::performOperation()`

- Gate it behind `BLACK_OPS_HOURS_AFTER_ROUND_START` (72 hours = day 4)
- Deduct `SCOUT_SPY_STRENGTH_COST` (5%) spy strength
- Call `$this->valuablesService->attemptScoutDiscovery($dominion, $target)`
- Returns success/failure message; no info op created

### 2.5 `ValuablesService` — `src/Services/Dominion/ValuablesService.php`

```php
public function attemptPassiveDiscovery(Dominion $attacker, Dominion $target, string $agent = 'spies'): string
// Rolls PASSIVE_DISCOVERY_CHANCE. On hit, calls createValuable() and returns discovery phrase.
// Returns '' on miss.

public function attemptScoutDiscovery(Dominion $attacker, Dominion $target): array
// Rolls SCOUT_DISCOVERY_CHANCE. Returns ['success' => bool, 'message' => '...', 'alert-type' => '...']

private function createValuable(Dominion $attacker, Dominion $target): Valuable
// - Selects rarity via ValuablesHelper::selectRarity()
// - Selects random type from ['relic', 'jewelry', 'artwork', 'equipment', 'text']
// - Generates name via ValuablesHelper::generateName()
// - Sets transfer_price from rarity config
// - Persists with status='discovered', discovered_at=now()
```

---

## Phase 3 — Espionage page tables (read-only)

### 3.1 Controller — `EspionageController::getEspionage()`

Add to the view data:

```php
'valuablesDiscovered' => Valuable::where('source_dominion_id', $dominion->id)
    ->whereIn('status', ['discovered', 'investigating', 'listed_for_transfer', 'transferred'])
    ->with('targetDominion')
    ->orderByDesc('discovered_at')
    ->get(),
'valuablesStolen' => Valuable::where('source_dominion_id', $dominion->id)
    ->where('status', 'stolen')
    ->with('targetDominion')
    ->orderByDesc('stolen_at')
    ->get(),
'valuablesHelper' => app(ValuablesHelper::class),
```

### 3.2 View — `espionage.blade.php`

Append two tables below the existing operation tables:

**Table 1 — "Valuables Discovered"**

Columns: Discovered (relative time) | Name (rarity · type) | Target (linked) | Status / Progress | Actions

- If `investigating`: show `{pct}% (N ticks remaining)` with color class
- Otherwise: "Ready to steal"
- Actions per state:
  - `discovered` / `transferred`: `[Investigate]` link + `[Offer Xp]` POST form
  - `listed_for_transfer`: `[Unlist]` POST form
  - `investigating`: `[Cancel]` POST form

**Table 2 — "Valuables Stolen"**

Columns: Stolen (relative time) | Name | Target | Current Price | Actions

- Current price via `$valuablesHelper->getCurrentSalePrice($valuable)`
- `[Sell]` POST form

**Progress color logic:**

```blade
@php
    $elapsed = $valuable->investigation_started_at->diffInSeconds(now());
    $total   = $valuable->investigation_started_at->diffInSeconds($valuable->investigation_ends_at);
    $pct     = min(100, ($elapsed / $total) * 100);
    $colorClass = $pct < 25 ? 'text-danger'
                : ($pct < 50 ? 'text-warning'
                : ($pct < 75 ? 'text-primary' : 'text-success'));
@endphp
```

An info box on the right side links to the Valuables History page.

---

## Phase 4 — Investigation flow

### 4.1 `ValuablesActionService::startInvestigation()`

New file: `src/Services/Dominion/Actions/ValuablesActionService.php`

```php
public function startInvestigation(Dominion $dominion, Valuable $valuable, int $hours): array
```

**Validations (throw `GameException` on failure):**

1. `$valuable->source_dominion_id === $dominion->id`
2. `$valuable->status` is `discovered` or `transferred`
3. `$valuable->is_listed === false` (must unlist first)
4. `$hours` is a multiple of `INVESTIGATION_HOUR_STEP` and within `[MAX_INVESTIGATION_HOURS, MIN_INVESTIGATION_HOURS]`
5. **Staleness check:**
   - If `$valuable->hoursOld() >= EXPIRATION_HOURS` → mark as `failed`, throw `GameException`
   - Otherwise roll `(hoursOld / 48)²` — on hit, mark as `failed`, throw `GameException`
6. **Spy count check:** `$spiesNeeded = ceil($required / $hours)`; available spies = total raw spies − sum of `spies_assigned` across active investigations; must have `>= $spiesNeeded`
7. **Regen gate:** `getSpyStrengthRegen($dominion) - (activeCount * SPY_STRENGTH_PER_INVESTIGATION) - SPY_STRENGTH_PER_INVESTIGATION > 0`

**On success:**

```php
DB::transaction(function() use ($dominion, $valuable, $hours, $spiesNeeded) {
    $targetLand       = $this->landCalculator->getTotalLand($valuable->targetDominion);
    $config           = ValuablesHelper::getRarityConfig()[$valuable->rarity];
    $requiredSpyHours = $targetLand * $config['spy_hours_multiplier'];

    // Round completion to next hour boundary (always lands on a tick)
    $startTime = now()->startOfHour()->addHour();
    $endTime   = $startTime->copy()->addHours($hours);

    $valuable->required_spy_hours       = $requiredSpyHours;
    $valuable->spies_assigned           = $spiesNeeded;
    $valuable->status                   = 'investigating';
    $valuable->investigation_started_at = $startTime;
    $valuable->investigation_ends_at    = $endTime;
    $valuable->save();
});
```

### 4.2 `ValuablesActionService::cancelInvestigation()`

```php
public function cancelInvestigation(Dominion $dominion, Valuable $valuable): array
```

Validates source + status `investigating`. Resets to `discovered`, nulls all investigation fields, frees committed spies.

### 4.3 Spy strength regen penalty — `MilitaryCalculator::getSpyStrengthRegen()`

```php
// After existing regen calculation:
$activeInvestigations = Valuable::where('source_dominion_id', $dominion->id)
    ->where('status', 'investigating')
    ->count();
$regen -= $activeInvestigations * ValuablesHelper::SPY_STRENGTH_PER_INVESTIGATION;
// Returning a negative value is intentional — TickService caps at (100 - current),
// and the validator reads the raw value to gate new investigations.
```

### 4.4 Tick completion — `TickService::performTick()`

In the per-round section, add:

```php
$this->valuablesService->processRoundTick($round);
```

**`ValuablesService::processRoundTick(Round $round)`:**

```php
// Pass 1: Auto-complete investigations whose timer has elapsed
Valuable::forRound($round)
    ->where('status', 'investigating')
    ->where('investigation_ends_at', '<=', now())
    ->each(function (Valuable $v) {
        $v->status    = 'stolen';
        $v->stolen_at = now()->startOfHour();
        $v->is_listed = false;
        $v->save();
    });

// Pass 2: Expire anything older than 48 hours not yet resolved
Valuable::forRound($round)
    ->whereNotIn('status', ['sold', 'expired', 'failed'])
    ->where('discovered_at', '<=', now()->subHours(ValuablesHelper::EXPIRATION_HOURS))
    ->each(function (Valuable $v) {
        // Investigations that ran out of time → failed; others → expired
        $v->status = ($v->status === 'investigating') ? 'failed' : 'expired';
        $v->save();
    });
```

### 4.5 Routes

```php
$router->get('valuables/{valuable}/investigate') ->uses('Dominion\ValuablesController@getInvestigate') ->name('valuables.investigate');
$router->post('valuables/{valuable}/investigate')->uses('Dominion\ValuablesController@postInvestigate');
$router->post('valuables/{valuable}/cancel')     ->uses('Dominion\ValuablesController@postCancel')     ->name('valuables.cancel');
```

### 4.6 Investigation planning page — `ValuablesController::getInvestigate()`

Pass to view:

```php
'valuable'             => $valuable,
'dominion'             => $dominion,
'durationOptions'      => $this->buildDurationTable($dominion, $valuable),
// array of rows: [hours, spiesNeeded, totalStrengthCost, completesAt, disabled, disabledReason]
'availableSpies'       => $totalSpies - $sumOfActiveSpies,
'currentRegen'         => $this->militaryCalculator->getSpyStrengthRegen($dominion),
'activeInvestigations' => $activeCount,
```

**Duration table construction:**

```php
for ($hours = MAX_INVESTIGATION_HOURS; $hours <= MIN_INVESTIGATION_HOURS; $hours += STEP) {
    $spiesNeeded = ceil($required / $hours);
    $minSpies    = ceil($required / MIN_INVESTIGATION_HOURS);
    $maxSpies    = ceil($required / MAX_INVESTIGATION_HOURS);
    $disabled    = (
        $spiesNeeded < $minSpies
        || $spiesNeeded > $maxSpies
        || $spiesNeeded > $availableSpies
        || $regen - (($activeCount + 1) * SPY_STRENGTH_PER_INVESTIGATION) <= 0
    );
}
```

View: `app/resources/views/pages/dominion/valuables/investigate.blade.php`

The right info column shows: required spy-hours, available spies, current spy strength regen, and a warning when adding another investigation would make regen non-positive.

---

## Phase 5 — Sale flow

### 5.1 `ValuablesActionService::sellValuable()`

```php
public function sellValuable(Dominion $dominion, Valuable $valuable): array
```

Validates: source, status `stolen`.

```php
DB::transaction(function() use ($dominion, $valuable) {
    $price = $this->valuablesHelper->getCurrentSalePrice($valuable);
    $dominion->resource_platinum += $price;
    $valuable->status     = 'sold';
    $valuable->sold_price = $price;
    $valuable->save();
    $dominion->save(['event' => HistoryService::EVENT_ACTION_SELL_VALUABLE]);
});
```

### 5.2 New history event constants — `HistoryService`

```php
public const EVENT_ACTION_SELL_VALUABLE     = 'sell valuable';
public const EVENT_ACTION_TRANSFER_VALUABLE = 'transfer valuable';   // seller side
public const EVENT_ACTION_PURCHASE_VALUABLE = 'purchase valuable';   // buyer side
```

### 5.3 Route

```php
$router->post('valuables/{valuable}/sell')->uses('Dominion\ValuablesController@postSell')->name('valuables.sell');
```

---

## Phase 6 — Realm transfer marketplace

### 6.1 `ValuablesActionService::listValuable()` / `unlistValuable()`

```php
public function listValuable(Dominion $dominion, Valuable $valuable): array
// Validates: source, status in [discovered, transferred], not already listed
// Sets is_listed = true

public function unlistValuable(Dominion $dominion, Valuable $valuable): array
// Validates: source or same realm (from bounty board), is_listed = true
// Sets is_listed = false
```

### 6.2 `ValuablesActionService::purchaseValuable()`

```php
public function purchaseValuable(Dominion $buyer, Valuable $valuable): array
```

**Validations:**

1. `$valuable->is_listed === true`
2. `$buyer->realm_id === $valuable->sourceDominion->realm_id`
3. `$buyer->id !== $valuable->source_dominion_id`
4. `$buyer->resource_platinum >= $valuable->transfer_price`

```php
DB::transaction(function() use ($buyer, $valuable) {
    $seller = $valuable->sourceDominion;
    $price  = $valuable->transfer_price;

    $buyer->resource_platinum  -= $price;
    $seller->resource_platinum += $price;

    $valuable->source_dominion_id        = $buyer->id;
    $valuable->is_listed                = false;
    $valuable->transferred              = true;
    $valuable->status                   = 'discovered';
    $valuable->spies_assigned           = null;
    $valuable->required_spy_hours       = null;
    $valuable->investigation_started_at = null;
    $valuable->investigation_ends_at    = null;
    $valuable->save();

    $buyer->save(['event'  => HistoryService::EVENT_ACTION_PURCHASE_VALUABLE]);
    $seller->save(['event' => HistoryService::EVENT_ACTION_TRANSFER_VALUABLE]);

    $this->notificationService
        ->queueNotification('valuable_purchased', [
            'buyerDominionId' => $buyer->id,
            'valuableName'    => $valuable->name,
            'transferPrice'   => $price,
        ])
        ->sendNotifications($seller, 'irregular_dominion');
});
```

### 6.3 Bounty board — `BountyController::getBountyBoard()`

Add to view data:

```php
'realmValuablesListed' => Valuable::where('is_listed', true)
    ->forRound($dominion->round)
    ->whereHas('sourceDominion', fn($q) => $q->where('realm_id', $dominion->realm_id))
    ->with(['sourceDominion', 'targetDominion'])
    ->orderByDesc('discovered_at')
    ->get(),
'valuablesHelper' => app(ValuablesHelper::class),
```

Add a new section to `bounty-board.blade.php`:

**"Realm Valuables Available for Transfer"** table:

Columns: Listed By | Name (rarity · type) | Target | Spy-Hours Required | Price | Actions

- If `$valuable->source_dominion_id === $dominion->id` → `[Unlist]`
- Otherwise → `[Purchase]` (disabled + tooltip if insufficient platinum)

### 6.4 Routes

```php
$router->post('valuables/{valuable}/list')    ->uses('Dominion\ValuablesController@postList')    ->name('valuables.list');
$router->post('valuables/{valuable}/unlist')  ->uses('Dominion\ValuablesController@postUnlist')  ->name('valuables.unlist');
$router->post('valuables/{valuable}/purchase')->uses('Dominion\ValuablesController@postPurchase')->name('valuables.purchase');
```

### 6.5 Notification type registration

Register `valuable_purchased` in `NotificationHelper`'s settings map under the `irregular_dominion` category, alongside existing entries like `repelled_spy_op`.

---

## Phase 7 — History page + sidebar badges

### 7.1 History page

Route:

```php
$router->get('valuables/history')->uses('Dominion\ValuablesController@getHistory')->name('valuables.history');
```

Query:

```php
Valuable::where('source_dominion_id', $dominion->id)
    ->forRound($dominion->round)
    ->whereIn('status', ['sold', 'expired', 'failed'])
    ->with('targetDominion')
    ->orderByDesc('updated_at')
    ->get();
```

Compute summary stats from the same collection for the right info column:
- Total attempts, successful thefts, failed thefts, sold count, expired count, total platinum earned, overall success rate

View: `app/resources/views/pages/dominion/valuables/history.blade.php`

Columns: Completed | Valuable (name + rarity · type) | Target | Result (Success / Failed) | Sale Price | Status (Sold / Expired / Theft Failed)

### 7.2 Sidebar badges — `master.blade.php`

Inject into the sidebar view data (via view composer or `AbstractDominionController`):

```php
'valuablesDiscoveredCount' => Valuable::where('source_dominion_id', $dominion->id)
    ->whereIn('status', ['discovered', 'listed_for_transfer', 'transferred'])
    ->count(),
'valuablesStolenCount' => Valuable::where('source_dominion_id', $dominion->id)
    ->where('status', 'stolen')
    ->count(),
```

Espionage sidebar link:

```blade
Espionage
@if($valuablesDiscoveredCount > 0)
    <span class="badge badge-primary">{{ $valuablesDiscoveredCount }}</span>
@endif
@if($valuablesStolenCount > 0)
    <span class="badge badge-info">{{ $valuablesStolenCount }}</span>
@endif
```

Per spec §10.7: `listed_for_transfer` items **do** count toward the primary (discovered) badge.

---

## File inventory

| Type | File path |
|---|---|
| Migration | `app/database/migrations/<ts>_create_valuables_table.php` |
| Model | `src/Models/Valuable.php` |
| JSON data | `app/resources/data/valuables-names.json` |
| Helper | `src/Helpers/ValuablesHelper.php` |
| Discovery service | `src/Services/Dominion/ValuablesService.php` |
| Action service | `src/Services/Dominion/Actions/ValuablesActionService.php` |
| Controller | `src/Http/Controllers/Dominion/ValuablesController.php` |
| View: investigate | `app/resources/views/pages/dominion/valuables/investigate.blade.php` |
| View: history | `app/resources/views/pages/dominion/valuables/history.blade.php` |
| **Modified files** | |
| | `src/Helpers/EspionageHelper.php` — add `scout_for_valuables` op + `isValuablesOperation()` |
| | `src/Services/Dominion/Actions/EspionageActionService.php` — passive discovery hook + scout handling |
| | `src/Services/Dominion/Actions/SpellActionService.php` — passive discovery hook |
| | `src/Calculators/Dominion/MilitaryCalculator.php` — regen penalty from active investigations |
| | `src/Services/Dominion/TickService.php` — call `ValuablesService::processRoundTick()` |
| | `src/Services/Dominion/HistoryService.php` — 3 new event constants |
| | `src/Services/NotificationHelper.php` — register `valuable_purchased` notification type |
| | `app/routes/web.php` — 7 new routes |
| | `app/resources/views/pages/dominion/espionage.blade.php` — two new tables |
| | `app/resources/views/pages/dominion/bounty-board.blade.php` — realm marketplace section |
| | `app/resources/views/layouts/master.blade.php` — sidebar badges |

---

## Non-obvious implementation notes

1. **`discovered_at` vs `created_at`** — Store explicitly so transfer never resets the 48-hour clock.

2. **Hour-alignment** — `investigation_ends_at = now()->startOfHour()->addHour()->addHours($hours)`. This ensures tick completion always fires on a real tick boundary.

3. **Regen gate validation** — Check `getSpyStrengthRegen() - ((activeCount + 1) * 2.0) > 0` before starting a new investigation. Pass `activeCount` (not `activeCount + 1`) to the calculator when *displaying* current regen on the planning page; pass `activeCount + 1` only during start-validation.

4. **Available spies** — `military_spies + (military_assassins * 2) + (unit counts_as_spy * perk_value)` minus `sum(spies_assigned of active investigations)`. This mirrors `MilitaryCalculator::getSpyRatioRaw()` logic but uses absolute counts, not ratios.

5. **Scout operation gating** — Use the existing `BLACK_OPS_HOURS_AFTER_ROUND_START` constant (72h) already on `EspionageActionService`. The spec's "day-4 black-ops gating" maps to this constant.

6. **Price decay** — Stolen valuables never expire; decay stops at the rarity minimum after 48h and stays there permanently.

7. **Listed status in discovered badge** — Query `whereIn('status', ['discovered', 'listed_for_transfer', 'transferred'])` for the primary badge count (spec §10.7).

8. **Tick expiration safety net** — The expiration pass in `processRoundTick()` must also catch `investigating` valuables whose `investigation_ends_at` is still in the future but whose `discovered_at` is past 48h. These get marked `failed` (not `expired`).

---

## Testing checkpoints per phase

| Phase | What to test |
|---|---|
| 1 | Migration runs; model instantiates; name generator covers all rarity/type combos; rarity selection produces correct distribution across the 0–1 score range |
| 2 | Passive roll fires ~1% on info ops; scout rolls ~10% and costs 5% spy strength; no roll on theft or black ops |
| 3 | Espionage page renders tables; counts are correct; all states display without errors |
| 4 | Duration table disables rows correctly; staleness fails at correct ages; tick auto-completes; spy regen is reduced by active investigations; cancel frees committed spies |
| 5 | Sale awards correct decayed price at various times after theft; history event is logged; item moves to `sold` |
| 6 | Listing blocks investigation; purchase transfers platinum and sourceship; original `discovered_at` is unchanged; seller receives notification |
| 7 | History page summary stats are accurate; sidebar badges show correct counts; listed items count in primary badge |
