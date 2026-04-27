# Realms & Diplomacy

## Overview

OpenDominion is fundamentally a team game. Dominions do not compete as individuals — they compete as realms. A realm is a group of 10–15 dominions who share a communication channel, cooperative goals (wonders, war), and mutual victory conditions. The realm formation process tries to balance player skill, social compatibility, and strategic playstyle diversity. Once formed, realms interact through war declarations, guard memberships, and coordinated wonder assaults. The diplomatic state between realms determines what actions are available and what combat bonuses apply.

---

## Core Concepts

**Realm** — A team of dominions within a round. All members share realm resources (Message of the Day, council threads), government roles, and wonder bonuses. Members cannot attack each other.

**Pack** — A pre-formed group of 2–4 players who register for a round together, guaranteeing they are placed in the same realm.

**Graveyard (Realm 0)** — A special non-competitive realm where inactive, unassigned, or abandoned dominions are held. The graveyard has no government, no wonders, and is excluded from competitive play.

**Alignment** — Each race belongs to either Good or Evil. In non-mixed rounds, realms are constrained to a single alignment. In mixed-alignment rounds, any race can join any realm.

**War** — A formal diplomatic state between two realms. Wars enable combat bonuses, wonder attacks, and war spell access between the opposing realms.

**Guard** — Realm-level organizations (Royal Guard, Elite Guard, Black Guard) that provide certain bonuses and impose certain restrictions on their members.

---

## Realm Formation

### Pack Registration

Before a round begins, players register and optionally form packs. A pack is a pre-committed group of 2–4 players guaranteed to be placed together. Players who register alone are solo players and will be assigned by the algorithm.

Pack registration closes at the **realm assignment date**, which occurs 96 hours (4 days) before the round starts. Single-member packs are dissolved at this point — their creator becomes a solo player.

Pack rating is calculated as the root-mean-square of all member ratings. This aggregate score is used to balance realm strength.

### Realm Count

The game calculates how many realms to create based on the number of registered players. Constraints enforce a minimum and maximum realm count. Large packs (those above a size threshold) anchor the initial realm count, and the algorithm adjusts up or down from there.

### Assignment Algorithm

The assignment process runs in phases, balancing multiple competing objectives:

**Phase 1 — Anchor realms from large packs.** Each large pack forms the seed of a realm. Additional empty realms are created to reach the target realm count.

**Phase 2 — Assign small packs.** Each small pack is scored against every available realm using:
- **Compatibility score** — Positive if pack members have endorsed existing realm members in past rounds; penalized heavily if there are known dislikes. This attempts to place friends together and keep enemies apart.
- **Playstyle score** — Evaluates how the pack's self-reported playstyle (attacker/converter/explorer/ops affinity scores) fits the realm's current composition. Realms benefit from diverse playstyles; the algorithm penalizes creating compositions that are too skewed in one direction.
- **Balance score** — Encourages placements that equalize total realm ratings across all realms.
- **Opportunity cost** — Checks whether other unassigned packs have stronger claims to that realm slot.

**Phase 3 — Assign solo players.** New players (low rating) are distributed round-robin to equalize realm sizes. Experienced players are scored against each realm using the same multi-factor algorithm, assigned highest-rating first.

**Phase 4 — Optimization passes.** After initial assignment:
- Size balancing: solo players are moved from oversized to undersized realms until sizes are within 1.
- Random swap testing: pairs of solo players from different realms are swapped if the swap improves compatibility or balance above a threshold. Up to 50 iterations run.

**Non-Discord players** are separated into their own realms (capped at a smaller size) to avoid mixing with the primary Discord-integrated player pool.

### Post-Assignment

Once assigned, dominions cannot change realms. The realm is the player's permanent team for the entire round. Dominions who go inactive during the round are eventually moved to the graveyard.

---

## Realm Government

Each realm has a set of government roles that can be filled by member dominions:

| Role | Purpose |
|---|---|
| Monarch | Elected realm leader; primary voice for diplomatic decisions |
| General | Military coordination |
| Spymaster | Espionage coordination |
| Magister | Magic/research coordination |
| Court Mage | Can cast friendly spells on realmmates |
| Grand Magister | Can cast friendly spells on realmmates |
| Jester | Ceremonial/social role |

Roles are optional — realms function without them. The Monarch is chosen by majority vote among realm members. Court Mage and Grand Magister roles are the most mechanically significant: they confer the ability to cast beneficial spells on realmmates (Arcane Ward, Illumination, Spell Reflect).

---

## War

### Declaration

A realm can declare war on one other realm at a time. Only one simultaneous outgoing war declaration is permitted. War is unilateral — one realm declares, and the other is the target regardless of consent.

### War Timeline

| Phase | Timing |
|---|---|
| Declaration | War created; not yet active |
| Active (escalated) | 24 hours after declaration |
| Cancellable | 24 hours after activation |
| Auto-expiry | 108 hours after activation (approximately 4.5 days) |
| Post-cancellation cooldown | 12 hours after cancellation before war fully expires |
| Redeclaration cooldown | 48 hours after expiry before the same target can be re-declared on |

### War States

**Engaged** — War has been declared and is not yet cancelled. May or may not be escalated.

**Escalated (active)** — War has passed the 24-hour activation window. This is when combat bonuses and wonder attack permissions apply.

**One-sided war** — Only one realm has declared on the other. The declaring realm gets a combat bonus; the target does not (unless they declare back).

**Mutual war** — Both realms have active escalated war declarations against each other. Both sides receive a larger combat bonus. Wonder attacks between the two realms are permitted only in mutual escalated war.

**Cancelled** — One realm has ended their war declaration. A 12-hour cooldown follows before the war fully disappears.

**Expired** — Wars automatically cancel after approximately 4.5 days of being escalated. This prevents indefinite wars and creates natural diplomatic rhythm.

### War Bonuses

War status modifies several game mechanics:

| Condition | Combat Bonus | Land Loss | Hostile Spell Duration |
|---|---|---|---|
| No war | Baseline | Baseline | Baseline |
| One-sided war (escalated) | +4% offense | +10% land loss | +2 hours |
| Mutual war (both escalated) | +8% offense | +20% land loss | +4 hours |

Wonder attacks are only permitted between realms in mutual escalated war. Cyclone spells against owned wonders require the same condition.

Mana production can also increase during wartime through specific tech perks that scale with the number of active wars.

---

## Guard Organizations

Guards are realm-level (and cross-realm) organizations with specific bonus/restriction profiles.

### Royal Guard

Membership provides a bonus to certain gameplay parameters but imposes a platinum tax on all member dominions. Royal Guard members cannot attack wonders. Members gain access to the `Ross' Benevolence` tech perk effect (guard tax reduction).

### Elite Guard

A higher tier of guard membership with additional benefits and restrictions.

### Black Guard

The most aggressive organization. Key features:
- Members can cast war spells and perform war operations against each other's targets more freely (mutual Black Guard membership enables war ops without a formal war declaration).
- Failed spy operations result in partial spy recovery (re-queued for training rather than permanently lost).
- Members may have access to specific spells (`Delve into Shadow`) and broader offensive action windows.
- Black Guard members cannot join the Royal Guard simultaneously.

---

## Realm Communication

Each realm has:
- **Message of the Day (MOTD)** — Set by the Monarch. Visible to all realm members on login.
- **Council** — A threaded discussion board visible only to realm members. The primary coordination channel within the game.
- **Discord Integration** — Realms can optionally integrate with a Discord server, providing a real-time communication channel. Players who opt out of Discord are placed in non-Discord realms.

---

## Interactions With Other Systems

- **[Military](04-military.md)** — Cannot invade realmmates. War status provides OP bonus and increased land loss. The 40% and 5:4 home defense rules apply regardless of diplomatic state.
- **[Magic](05-magic.md)** — Friendly spells (Arcane Ward, Illumination, Spell Reflect) can only be cast on realmmates by Court Mage/Grand Magister roles. Hostile spell duration is extended by war status.
- **[Espionage](06-espionage.md)** — Cannot spy on realmmates. Black Guard mutual membership enables war operations without formal war. Spy loss recovery applies to Black Guard members.
- **[Wonders](09-wonders.md)** — Wonder attacks on owned wonders require mutual escalated war. The one-wonder-per-realm limit creates scarcity that drives inter-realm competition. Wonder bonuses apply to all realm members.
- **[Round Structure](11-round-structure.md)** — Realm assignment happens 96 hours before round start. Pack registration closes at assignment. Graveyard receives inactive players throughout the round.

---

## Player Decision Space

**Pack formation** — The most impactful pre-round social decision. Playing with known, coordinated teammates provides substantial advantages in communication and strategic alignment. Pack size is capped to prevent large premade groups from dominating.

**War timing** — Declaring war too early gives the enemy 24 hours to prepare before escalation. Declaring too late wastes potential offensive windows. The auto-expiry prevents perpetual wars, so active realms must re-declare if conflict continues past the expiry window.

**Mutual vs. one-sided war** — A realm that declares war first gets a smaller bonus than if the enemy declares back. Forcing a mutual war situation (by being aggressive enough that the enemy retaliates) can be strategically valuable.

**Guard membership** — Royal Guard provides bonuses but prevents wonder attacks and imposes a tax. Black Guard enables broader offensive operations but cuts off Royal Guard benefits. The choice shapes the realm's entire diplomatic and offensive posture.

**Government role allocation** — The Court Mage and Grand Magister roles have real mechanical impact (friendly spell access). Choosing who holds them and ensuring active players fill those roles can meaningfully contribute to realm defense.

> **Note:** The realm assignment algorithm's compatibility scoring system means that player reputation carries over between rounds. Players who behave poorly (generating dislikes in the system) may find themselves systematically placed in less compatible realms. The social layer is not purely cosmetic — it has mechanical consequences through the assignment process.
