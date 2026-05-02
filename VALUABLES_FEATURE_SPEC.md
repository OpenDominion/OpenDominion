# Valuables Feature Specification

A design-level description of the **Valuables** feature implemented on the `valuables` branch. This document focuses on *what the feature does* and *how it behaves* rather than how the current code is structured, so the feature can be re-implemented cleanly on a different branch.

---

## 1. Concept Summary

Valuables are randomly-discovered, named treasure items that a dominion learns exist inside an enemy dominion. Once discovered, a valuable can be:

1. **Investigated and stolen** (a multi-hour spy operation that completes automatically), then **sold** to a fluctuating black-market for platinum.
2. **Listed for transfer** to a realm mate at a fixed, rarity-based price (a way to monetize a discovery you cannot or do not want to steal yourself).

The feature adds a passive, low-frequency "loot" layer on top of existing information-gathering operations and creates an ongoing economic mini-game around timing sales against a fluctuating market price.

---

## 2. Discovery

### 2.1 Trigger

Whenever a player successfully performs an **information gathering operation** of either type:

- **Espionage info ops** (the spy-driven information-gathering buttons), or
- **Magic info spells** (the wizard-driven information-gathering buttons),

a discovery roll is made *in addition to* the normal info result. The success message of the operation is appended with the discovery (e.g. *"Your spies have discovered a Legendary work of art in the target's possession!"*).

### 2.2 Discovery roll

- Flat **1% chance** per successful info op.
- Resource theft and black ops do **not** trigger discovery.

### 2.3 Rarity selection

When a discovery occurs, rarity is selected on a 0.0–1.0 score derived from two normalized inputs, averaged together:

- **Land score** — target dominion's total land normalized to a band (the spec uses `(land - 500) / 7500`, clamped 0–1, so 500 land ≈ 0.0 and 8000+ land ≈ 1.0).
- **Spy score** — attacker's raw spy ratio (spies per acre), clamped to 0–1.

The averaged score is mapped linearly across five rarity tiers (`round(score * 4)`):

| Rarity     | Sale price (min–max) | Required spy-hours multiplier | Realm transfer price |
|------------|----------------------|-------------------------------|----------------------|
| Common     | 5,000–10,000         | 0.5 × target land             | 2,500                |
| Uncommon   | 10,000–25,000        | 1.0 × target land             | 5,000                |
| Rare       | 25,000–50,000        | 2.0 × target land             | 10,000               |
| Epic       | 50,000–100,000       | 3.0 × target land             | 20,000               |
| Legendary  | 100,000–250,000      | 5.0 × target land             | 40,000               |

**Effect:** Bigger targets attacked by spy-heavy attackers have a higher chance of being legendary, while small targets attacked by spy-thin attackers tend to drop common.

### 2.4 Item generation

Each valuable has:

- A **type** (chosen uniformly at random): `relic`, `jewelry`, `artwork`, `equipment`, or `text`.
- A **name** built from three random pieces sampled from per-type word lists: `<prefix> <base> <suffix>` (e.g. *"Ancient Crown of the First King"*, *"Masterwork Painting by Leonardo"*). Each type has its own thematic vocabulary.
- A **rarity** (set above).

The vocabulary lives in a static data file (json) with five lists per type: 15 prefixes, 15 bases, 15 suffixes. This gives ≈3,375 distinct names per type.

### 2.5 Discovery display

The discovery message uses an indefinite article + rarity + type, with type-specific phrasing:

- `relic`, `text` → "a/an `<rarity>` `<type>`" (e.g. *"a Rare relic"*)
- `jewelry` → "a/an `<rarity>` item of jewelry"
- `equipment` → "a/an `<rarity>` piece of equipment"
- `artwork` → "a/an `<rarity>` work of art"

### 2.6 Persistence

A discovered valuable is persisted with:

- `source_dominion_id` (the discovering player), `target_dominion_id` (the holder), `round_id`
- `rarity`, `type`, `name`
- A discovery timestamp (used for staleness/expiration)
- Investigation/completion/sale state (all initially empty)
- Transfer-marketplace flags (initially false)

A discovered valuable is **owned by the discoverer**, even though it physically "lives" in the target dominion until stolen. The target dominion is unaware (no notification).

---

## 3. Lifecycle States

A single valuable progresses through these states:

```
                 +------------------------+
discovered ----> | listed_for_transfer    |---> transferred (new owner, back to "discovered")
   |             +------------------------+
   |                       ^
   |                       |  (list / unlist)
   v                       v
investigation_started ---> investigation_completes_at reached
   |                                       |
   |  (cancel, returns to discovered)      v
   |                                  stolen (success=true, completed_at set)
   |                                       |
   |                                       v
   |                                    sold (sold_at, platinum_received)
   v
expired or staleness-failed (success=false, completed_at set)
```

Key flags / fields drive the state machine:

- `created_at` — discovered
- `investigation_started_at` / `investigation_completes_at` — in progress
- `completed_at` + `success=true` — stolen, available to sell
- `completed_at` + `success=false` — failed (expired or stale)
- `sold_at` / `platinum_received` — sold
- `listed_for_transfer` — currently on the realm marketplace
- `transferred` — has been sold to a realm mate at least once

Helper checks the system needs:

- `isDiscovered()` — has been created, not yet completed
- `isBeingInvestigated()` — investigation started, not yet completed
- `isCompleted()` — has a `completed_at`
- `isStolen()` — completed with `success = true`
- `isSold()` — has `sold_at`
- `isReadyForTheft()` — investigation_completes_at is in the past, not yet completed
- `isEligibleForTransfer()` — discovered, not investigating, not transferred, not already listed
- `isListedForTransfer()` — listed and not completed

---

## 4. Investigation (Theft Planning)

### 4.1 Duration / spy assignment model

Required spy-hours = `target_land * rarity_multiplier` (computed at investigation start using the target's *current* land, then frozen on the valuable). The player picks how many hours the heist takes; the system derives required spies. Hours must be a multiple of 6.

The UI presents a fixed table of duration choices: **6, 12, 18, 24, 30, 36 hours**.

For each row:

- `spies_needed = ceil(required_spy_hours / hours)`
- The row is selectable only if all of these hold:
  - `spies_needed >= min_spies` (where `min_spies = ceil(required / 36)` — the slowest, fewest-spies option)
  - `spies_needed <= max_spies` (where `max_spies = ceil(required / 6)` — the fastest, most-spies option)
  - Player has at least `spies_needed` *available* spies (i.e. not already assigned to other active investigations)
  - Starting this investigation would not push the player's spy strength regen below 0 (see §4.4)

Selecting a row submits the form and starts the investigation.

### 4.2 Available spies

At any time, the player's **available spies** = `total spy count − sum of spies_assigned across active investigations`. Once a spy is assigned, it counts toward this drain until the investigation finishes, fails, or is canceled.

> Note: `spy count` here is the *raw count* of spies (military spies + 2× assassins, etc.), not the spy ratio per acre. The branch refactors the existing spy/wizard math to expose a `getSpyCount` / `getWizardCount` alongside the existing ratio helpers, because the feature works in absolute spy counts.

### 4.3 Staleness check at start of investigation

Older discoveries are riskier to act on. When the player tries to start an investigation:

- If the discovery is **≥ 48 hours old**, the investigation fails immediately (the valuable is marked completed with `success=false`).
- Otherwise, an accelerating staleness chance `(hours_since_discovery / 48)²` is rolled. On hit, same outcome: instant failure.

This means a discovery that's a few hours old is essentially safe (1–4% fail chance), but a discovery in the high-30s is very likely to fail outright.

### 4.4 Spy strength regeneration penalty

Each **active** investigation imposes a flat **−2 percentage points / hour** on spy strength regeneration for the entire duration of the investigation. With normal regen of ~4%/hr, a player can comfortably run 1 investigation, 2 is borderline, and starting a 3rd is blocked by the validator unless their regen is high.

The investigation form previews the total spy-strength cost over the duration (e.g. 6 hours = 12%, 36 hours = 72%) and warns when adding another would make regen non-positive.

### 4.5 Investigation start

On submit, the system writes:

- `spies_assigned`, `spy_hours` (frozen required hours), `investigation_started_at = now`
- `investigation_completes_at = now + hours_to_complete`, **rounded down to the next hour boundary** so completion happens at a tick boundary

While investigating, the valuable shows progress on the espionage page:

- Numeric percentage `(time elapsed / total time) * 100`
- A color class shifting from red → yellow → blue → green as progress passes 25 / 50 / 75%
- Ticks remaining until completion

### 4.6 Cancellation

A player can cancel an in-progress investigation at any time. Cancellation:

- Resets `spies_assigned`, `spy_hours`, `investigation_started_at`, `investigation_completes_at` to null
- Returns the valuable to the *discovered* state (still owned, can be re-investigated or listed for transfer)
- Frees the assigned spies immediately

There is no penalty for cancellation beyond the spy strength already burned during the investigation.

### 4.7 Automatic completion on tick

Theft completion is **fully automatic** — there is no second action by the player. A scheduled job on every hourly game tick runs two passes per round:

1. **Auto-complete:** any valuable whose `investigation_completes_at` is now ≤ `now` and which isn't yet completed → mark `completed_at = now`, `success = true`.
2. **Expire:** any valuable that's older than 48 hours and isn't yet completed → mark `completed_at = now`, `success = false`. (This catches discovered-but-never-investigated valuables and any in-progress valuables that escape the auto-complete step.)

Because completion happens at tick boundaries and `investigation_completes_at` was rounded to an hour boundary, theft *always* resolves on a tick, never mid-tick.

Investigations always succeed if they reach completion — there is no resolution roll. The only ways an investigation fails are: staleness at start, cancellation by the player, or the 48-hour cap forcing expiration before completion.

---

## 5. Selling Stolen Valuables

### 5.1 Market price model

Once a valuable is stolen, its sale price floats on a deterministic **random walk** in the rarity's min/max band:

- Starting price = midpoint of `(min, max)`.
- Each hour after theft, price moves by a single random step drawn from `[-1, +1] × volatility × range`, with **volatility = 10%** of the band width (`(max - min) * 0.1`).
- Price is clamped to `[min, max]` after each step.
- The walk runs hour-by-hour for up to **48 hours after theft**; after that, price is constant at whatever it was at hour 48.

The walk is **deterministic per valuable** (the RNG is seeded by the valuable's id), so the same valuable always shows the same price history to all viewers and across page reloads.

### 5.2 Price display

The espionage page shows a **24-hour price sparkline** alongside each unsold stolen valuable, with the current (most-recent) price highlighted. The sparkline is rendered client-side from a server-computed series of integer prices.

If the valuable was stolen less than 24 hours ago, the early hours of the series are padded with the starting (mid) price so the sparkline length is consistent.

### 5.3 Sale

Pressing **Sell** transfers `current_price` platinum to the dominion and marks the valuable `sold_at = now`, `platinum_received = current_price`. A history event is logged so the platinum gain shows up in the dominion's activity history (alongside other platinum-affecting actions like raid rewards).

There is no time pressure beyond the sidebar info copy that suggests "you'll have 12 hours to sell" — in practice the price simply stops moving after 48 hours, and stolen valuables remain sellable indefinitely. Tuning this is a balance choice.

---

## 6. Realm Transfer Marketplace

A discovered valuable that the discoverer doesn't want to investigate themselves (e.g. they don't have enough spies, or it's too rare for them to handle) can be **listed for transfer** to a realm mate at a flat, rarity-based platinum price.

### 6.1 Listing

Eligibility for listing: the valuable must be `discovered`, **not** investigating, **not** completed, **not** already listed, and **not** already transferred. Listing simply flips a `listed_for_transfer` flag.

While listed, the valuable cannot be investigated (the validator blocks it; the discoverer must unlist first).

### 6.2 Discovery/listing UI

- On the **espionage page**, the discoverer sees both an "Investigate" and "Offer (Xp)" action on each eligible discovery, plus an "Unlist" action while it's listed.
- On the **bounty board** (chosen because it's the natural realm-marketplace surface), realm mates see all currently-listed valuables in their realm. Each row shows seller, item name + rarity/type, the original target (so the buyer knows the difficulty), the spy-hours required, and either a **Purchase** button (for buyers with enough platinum) or an **Unlist** button (if the listing is theirs).

### 6.3 Purchase

Purchase requires:

- Listing must be active (still listed, not completed)
- Buyer must be in the same realm as the seller
- Buyer must have at least `transfer_price` platinum
- Buyer cannot be the seller

On purchase, a single transaction:

1. Transfers `transfer_price` platinum from buyer to seller (logged as separate history events: `purchase valuable` for the buyer, `transfer valuable` for the seller).
2. Reassigns the valuable to the buyer (`source_dominion_id` = buyer), clears all investigation state, sets `listed_for_transfer = false`, sets `transferred = true`.
3. Notifies the seller via the in-game notifications system.

Effectively, the buyer now owns a **freshly discovered** valuable they can investigate themselves. The discovery `created_at` is *not* reset, however — staleness still ticks from the original discovery time, so a buyer needs to act before the 48-hour clock runs out.

### 6.4 Unlisting

Either the seller (from espionage or the bounty board) can remove a listing. Unlisting just flips the flag back — the valuable returns to discovered state.

---

## 7. UI Surfaces

### 7.1 Espionage page additions

The espionage page gets two new tables underneath the existing operation buttons:

1. **Valuables Discovered** — every active (not yet completed) valuable owned by the player, with columns: Discovered (relative time), Name (+ rarity/type), Target (linked to op center), Spies, Progress (or "Ready to steal"), Actions.
   - Action depends on state: `Investigate` link (opens duration-picker page), `Offer` button (lists for transfer), `Unlist` button (removes listing), `Cancel` button (cancels in-progress investigation).
2. **Valuables Stolen** — every stolen-but-not-sold valuable, with columns: Stolen (relative time), Name, Target, Price History (sparkline of last 24h), Current Price, Actions (Sell button).

A small info box on the right links to the Valuables History page.

### 7.2 Investigation planning page

A dedicated page reached from the espionage table's *Investigate* link. Shows the valuable's name + target + flavor copy, then a duration table (rows for 6, 12, 18, 24, 30, 36 hours) with: Duration, Spies Required, Total Spy Strength cost, Completes At timestamp, and a Select button. Invalid rows show why they're disabled (not enough spies, too few/many for bounds, would cause negative spy regen).

The right info column shows: required spy-hours, available spies, current spy strength regen, and a warning calculating how many simultaneous investigations the player can sustain.

### 7.3 Valuables history page

A dedicated read-only history page lists every completed valuable (sold, expired, or theft-failed) for the round, with columns: Completed timestamp, Valuable (name + rarity/type), Target, Result (Success / Failed), Sale Price (or em-dash), Status (Sold / Expired / Theft Failed).

The right info column shows summary stats for the round: total attempts, successful thefts, failed thefts, sold count, expired count, total platinum earned, and overall success rate.

### 7.4 Bounty board addition

The bounty board grows a new section, **"Realm Valuables Available for Transfer"**, listing all active realm listings (one row per listing, including the player's own — those rows show `Unlist` instead of `Purchase`).

### 7.5 Sidebar badge

The sidebar's *Espionage* link gains two numeric badges (rendered to the right of the label):

- A **primary-colored** badge with the number of *discovered, not-yet-investigated* valuables (things the player needs to act on).
- A **blue** badge with the number of *stolen but unsold* valuables (things the player can monetize).

---

## 8. Game-economy interactions

- **Notifications:** the seller gets an in-game notification when a realm mate purchases their listing.
- **History service events:** new event types are recorded in the dominion history so the timeline shows: `sell valuable`, `transfer valuable`, `purchase valuable`. These let the player audit platinum changes from the feature.
- **No tech / hero / wonder hooks** in the initial implementation — discovery rate, rarity scaling, walk volatility, spy-hour multipliers, and timings are all global constants.
- **Black ops gating** does *not* apply: discoveries piggyback on info ops (which are allowed against any in-range dominion), so valuables can be discovered before day 4 of the round.

---

## 9. Tunable Constants

Centralized in one helper, easy to balance. Defaults on the branch:

| Constant                              | Value     | Meaning                                                    |
|---------------------------------------|-----------|------------------------------------------------------------|
| `DISCOVERY_CHANCE`                    | 0.01      | Per-info-op chance to discover a valuable                  |
| `EXPIRATION_HOURS`                    | 48        | Both staleness window and price-walk length                |
| `MIN_INVESTIGATION_HOURS`             | 36        | Slowest investigation option                               |
| `MAX_INVESTIGATION_HOURS`             | 6         | Fastest investigation option                               |
| `INVESTIGATION_HOUR_STEP`             | 6         | Granularity of duration choices                            |
| `SPY_STRENGTH_PER_INVESTIGATION`      | 2.0       | Spy strength regen reduction per active investigation      |
| `PRICE_VOLATILITY`                    | 0.1       | Step size as fraction of price band per hour               |
| Per-rarity `base_value_min/max`       | see §2.3  | Sale price band per rarity                                 |
| Per-rarity `spy_hours_multiplier`     | see §2.3  | Required spy-hours = land × multiplier                     |
| Per-rarity `transfer_price`           | see §2.3  | Fixed realm-transfer price                                 |

---

## 10. Persistence model (single table)

All state for a valuable lives in one record. Conceptual columns:

| Column                       | Type        | Notes                                              |
|------------------------------|-------------|----------------------------------------------------|
| `id`                         | int PK      | Used as RNG seed for the price walk                |
| `round_id`                   | int FK      | Scoping by round                                   |
| `source_dominion_id`         | int FK      | Current owner (changes on realm transfer)          |
| `target_dominion_id`         | int FK      | Where the valuable lives until stolen              |
| `rarity`                     | string      | One of: common, uncommon, rare, epic, legendary    |
| `type`                       | string      | One of: relic, jewelry, artwork, equipment, text   |
| `name`                       | string      | Generated `prefix base suffix`                     |
| `spies_assigned`             | int         | 0 when not investigating                           |
| `spy_hours`                  | int (null)  | Frozen at investigation start                      |
| `investigation_started_at`   | timestamp   | Null when not investigating                        |
| `investigation_completes_at` | timestamp   | Null until investigation starts                    |
| `completed_at`               | timestamp   | Null until success/failure                         |
| `success`                    | bool        | True only on theft success                         |
| `listed_for_transfer`        | bool        | Active marketplace listing                         |
| `transferred`                | bool        | Has been transferred at least once                 |
| `sold_at`                    | timestamp   | Null until sold                                    |
| `platinum_received`          | int (null)  | Recorded sale price                                |
| `created_at` / `updated_at`  | timestamps  | Standard                                           |

The schema is deliberately a single denormalized table — each valuable is small and short-lived (≤ days), and queries are mostly "by source dominion" or "by round + listed" with simple state filters. No event log table or audit trail is required beyond the existing dominion history events.

---

## 11. Behavior edge cases worth re-implementing

These are the non-obvious rules that make the feature feel right and should be preserved:

1. **Spy-hours frozen at start.** The required spy-hours are computed from target land *at investigation start* and stored on the valuable, so growing or shrinking the target mid-investigation does not change the cost.
2. **Hour-aligned completion.** Completion always lands on a tick boundary — never partial-hour math at sale time.
3. **Deterministic, per-valuable price walks.** The same valuable shows the same price history to anyone looking at it; the seed is the valuable id. Reset the RNG state after computing so it doesn't leak into other randomness.
4. **Transfer keeps the discovery clock.** The 48-hour staleness window does not reset on transfer — preventing endless realm-internal flipping.
5. **Listing blocks investigation.** A valuable that is currently listed cannot be investigated by the lister. The lister must unlist first.
6. **Staleness uses an accelerating curve.** `p_fail = (age / 48)²` rather than linear, so freshly discovered valuables are nearly safe and very stale ones are nearly always lost.
7. **Investigations are gated on regen, not on total drain.** The validator rejects starting an investigation if it would push *current* regen ≤ 0, regardless of current spy strength. This is what prevents 5+ simultaneous investigations.
8. **Listed valuables count as "discovered" in the sidebar badge** — the badge counts everything not currently investigating, not just things with no listing flag — so the player can see at a glance there's stuff sitting around they could act on.
9. **Tick-driven expiration is a safety net.** Even if the auto-complete step fails to run for some reason, the expiration step in the same tick guarantees the valuable cannot live forever in an inconsistent state.

---

## 12. Suggested re-implementation order

A clean rebuild on a different branch can proceed roughly in this order, each step independently testable:

1. **Schema + model + tunables.** Single table, the helper holding constants and the rarity/type tables, the json word lists, and the random-name generator.
2. **Discovery hook.** Wire the 1% roll into both info-op pipelines (espionage info + magic info) so the existing operations gain a small chance to spawn a valuable. Surface it in the success message.
3. **Espionage page list.** Read-only table showing discovered valuables — proves discovery + persistence works end-to-end.
4. **Investigation flow.** Duration picker → start → cancel → tick-driven auto-complete. Add the spy-strength regen penalty and the staleness check. At this point a valuable can be discovered, investigated, and stolen.
5. **Sale flow.** Random walk price, sparkline, sell action, history event.
6. **Realm transfer marketplace.** List/unlist/purchase, the bounty board section, the seller notification.
7. **History page + sidebar badges.** Polish: round-level summary stats, the two numeric badges on the espionage sidebar entry.

This ordering keeps every checkpoint shippable and avoids needing to rip out a half-built path later.
