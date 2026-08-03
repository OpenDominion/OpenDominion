<?php

namespace OpenDominion\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Models\UserFeedback;
use OpenDominion\Services\NotificationService;

/**
 * Non-persisted Player model
 */
class Player
{
    private const AFFINITY_PROPERTIES = [
        'attackerAffinity',
        'converterAffinity',
        'explorerAffinity',
        'opsAffinity',
    ];

    public string $id;
    public string $userId;
    public float $rating;
    public ?string $packId;
    public bool $hasDiscord = true;
    public array $favorability = []; // user_id => score
    public bool $hasKnownAffinities;

    // Playstyle affinities (0-100 for each category)
    public float $attackerAffinity = 0;
    public float $converterAffinity = 0;
    public float $explorerAffinity = 0;
    public float $opsAffinity = 0;

    /**
     * Create a new Player instance with given attributes
     *
     * Initializes a player object by setting any provided attributes that match
     * existing properties. Used to create player objects from database data.
     *
     * @param array $attributes Associative array of attribute names and values
     */
    public function __construct(array $attributes = [])
    {
        $hasKnownAffinities = array_key_exists('hasKnownAffinities', $attributes)
            ? (bool) $attributes['hasKnownAffinities']
            : $this->attributesContainKnownAffinities($attributes);

        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        $this->hasKnownAffinities = $hasKnownAffinities;

        if (!isset($this->userId)) {
            $this->userId = $this->id;
        }
    }

    /**
     * Create an assignment player using the user's effective rating.
     *
     * The database zero value remains a storage sentinel and is resolved here
     * before it can affect assignment scoring.
     */
    public static function fromUser(User $user, array $attributes = []): self
    {
        return new self(array_merge($attributes, [
            'rating' => $user->getEffectiveRating(),
            'hasKnownAffinities' => $user->hasKnownAffinities(),
            'attackerAffinity' => $user->getAffinity('attacker'),
            'converterAffinity' => $user->getAffinity('converter'),
            'explorerAffinity' => $user->getAffinity('explorer'),
            'opsAffinity' => $user->getAffinity('ops'),
        ]));
    }

    /**
     * Get favorability score with another player
     *
     * Returns the favorability rating this player has given to another player.
     * Positive values indicate endorsement, negative values indicate negative feedback.
     * Returns 0 if no feedback has been given.
     *
     * @param string $userId The user ID of the other player
     * @return float Favorability score (-1 to 1, typically)
     */
    public function getFavorabilityWith(string $userId): float
    {
        return $this->favorability[$userId] ?? 0;
    }

    private function attributesContainKnownAffinities(array $attributes): bool
    {
        $hasPositiveAffinity = false;

        foreach (self::AFFINITY_PROPERTIES as $property) {
            if (!array_key_exists($property, $attributes) || !is_numeric($attributes[$property])) {
                return false;
            }

            $hasPositiveAffinity = $hasPositiveAffinity || (float) $attributes[$property] > 0;
        }

        return $hasPositiveAffinity;
    }
}

/**
 * Non-persisted Pack model
 */
class PlaceholderPack
{
    public string $id;
    public Collection $members;
    public int $size;
    public bool $large;
    public float $rating;

    /**
     * Create a new PlaceholderPack instance
     *
     * Initializes a pack with the given members and calculates derived properties.
     * Large packs (>3 members) are automatically marked as such. The pack rating
     * is calculated as the average of all member ratings.
     *
     * @param string $id Unique identifier for the pack
     * @param Collection $members Collection of Player objects in this pack
     */
    public function __construct(string $id, Collection $members)
    {
        $this->id = $id;
        $this->members = $members;
        $this->size = $members->count();
        $this->large = $this->size > 3;
        $this->rating = $members->avg('rating') ?? 0;
    }

    /**
     * Calculate compatibility score with another pack
     *
     * Computes the total favorability between all members of this pack and
     * all members of another pack. Each pair of players contributes their
     * bidirectional favorability scores to the total.
     *
     * @param PlaceholderPack $pack The other pack to check compatibility with
     * @return float Total compatibility score (sum of all favorability ratings)
     */
    public function compatibilityWithPack(PlaceholderPack $pack): float
    {
        $totalScore = 0;

        foreach ($this->members as $member1) {
            foreach ($pack->members as $member2) {
                $totalScore += $member1->getFavorabilityWith($member2->userId);
                $totalScore += $member2->getFavorabilityWith($member1->userId);
            }
        }

        return $totalScore;
    }
}

/**
 * Non-persisted Realm model
 */
class PlaceholderRealm
{
    /**
     * @var int Affinity at which a player is treated as capable of a playstyle
     *
     * Affinities are the percentage of a player's past dominions flagged with a
     * playstyle, so 50 means they have spent half their rounds doing it — enough
     * to treat them as able and willing to do it again.
     */
    public const SPECIALIST_THRESHOLD = 50;

    /**
     * @var array Percentage of a realm's players that should be capable of each playstyle
     *
     * These are targets for the share of players clearing SPECIALIST_THRESHOLD, not
     * average affinities. Playstyles are not mutually exclusive, so the values do not
     * sum to 100 — one player can count toward several at once.
     *
     * Measured across the last three rounds: 174 users with computed affinities among
     * the 190 distinct users who cleared protection. Setting the targets to the observed
     * population shares keeps them achievable, so a balanced realm approaches zero
     * deviation rather than carrying a permanent offset.
     *
     * Attacker and explorer swing by roughly 10 points between individual rounds, so
     * these need re-measuring over several rounds rather than one, and a single round
     * is not a safe basis for changing them.
     */
    public const IDEAL_COMPOSITION = [
        'attackerAffinity' => 51,
        'converterAffinity' => 25,
        'explorerAffinity' => 55,
        'opsAffinity' => 28,
    ];

    /**
     * @var float Scale applied to the playstyle term in getCompatibilityScore()
     *
     * Calibrated against the rating balance term, which is the only other signal of
     * comparable size. At an assignment-time realm of ten players, moving a player 200
     * rating points further from target costs ~54.5 score units, and the best-versus-worst
     * playstyle spread across candidate realms is ~12.7 units at p95. A weight of 4 makes
     * a p95 composition improvement worth a little under 200 rating points. At a weight of
     * 1 it was worth ~47, which is why playstyle almost never changed a placement.
     */
    public const PLAYSTYLE_SCORE_WEIGHT = 4.0;

    /**
     * @var int Bound applied to the net favorability of a single pair
     *
     * Feedback is stored one row per pair per round and is never scoped to a round when
     * read, so a pair that has endorsed each other every round accumulates without limit —
     * observed up to +14 in production, more than an entire typical realm's worth of signal
     * from one relationship. Clamping per pair makes the term mean "these two get along"
     * rather than "these two have played together a lot".
     *
     * Measured over active players: this trims ~7% of positive pairs and ~0% of negative
     * ones, so it is almost entirely a control on friend-stacking.
     */
    public const FAVORABILITY_PAIR_LIMIT = 3;

    /**
     * @var int Bound applied to the summed positive favorability for one placement
     *
     * The per-pair limit alone still lets an organised group stack: ten members at +3 is
     * +30, which would let mutual endorsement history route around
     * MAX_PACKED_PLAYERS_PER_REALM. The highest total observed in a real assigned round was
     * +19, so this bounds the adversarial case without touching realistic ones.
     *
     * Deliberately not applied to the negative side, which must be able to stack into a veto.
     */
    public const FAVORABILITY_POSITIVE_LIMIT = 20;

    /**
     * @var float Weight applied to positive favorability
     *
     * Production feedback runs 5.9 positive to 1 negative, so this term is mostly a
     * friend-clustering mechanism. Kept deliberately modest: at this weight a p95 friend
     * cluster (+14) is worth ~128 rating points and the capped maximum ~183, so it nudges
     * placement without overriding rating balance.
     */
    public const FAVORABILITY_POSITIVE_WEIGHT = 2.5;

    /**
     * @var float Weight applied to negative favorability
     *
     * Weighted far above the positive side because downvotes are rare, deliberate, and the
     * cost of ignoring one is high. One downvoter is worth ~55 rating points — enough to
     * cancel a median friend cluster — and a sustained multi-round feud ~165.
     *
     * This replaces the former -100 conflict cliff, which required 11 net downvotes from
     * inside a single realm to fire and so never did. Scaling linearly reaches the same
     * magnitude at a reachable level and avoids a discontinuity that could make the
     * optimisation pass oscillate.
     */
    public const FAVORABILITY_NEGATIVE_WEIGHT = 15.0;

    public string $id;
    public Collection $players;
    public int $size;
    public float $rating;
    public bool $discordEnabled = true;

    /**
     * Create a new PlaceholderRealm instance
     *
     * Initializes a realm with the given players and calculates derived statistics.
     * Players are keyed by their ID for efficient lookup and the realm's size
     * and rating are automatically calculated.
     *
     * @param string $id Unique identifier for the realm
     * @param Collection $players Collection of Player objects in this realm
     * @param bool $discordEnabled Whether this realm allows Discord users
     */
    public function __construct(string $id, Collection $players, bool $discordEnabled = true)
    {
        $this->id = $id;
        $this->players = $players->keyBy('id');
        $this->discordEnabled = $discordEnabled;
        $this->update();
    }

    /**
     * Get all solo players in this realm
     *
     * Returns players who are not part of any pack (packId is null).
     * These are players who registered individually rather than as part
     * of a group.
     *
     * @return Collection Collection of solo Player objects
     */
    public function soloPlayers(): Collection
    {
        return $this->players->where('packId', null);
    }

    /**
     * Get all packed players in this realm
     *
     * Returns players who are part of a pack (packId is not null).
     * These are players who registered as part of a group.
     *
     * @return Collection Collection of packed Player objects
     */
    public function packedPlayers(): Collection
    {
        return $this->players->where('packId', '!=', null);
    }

    /**
     * Count the number of packed players in this realm
     *
     * Used to enforce the maximum packed players per realm constraint
     * during pack assignment.
     *
     * @return int Number of packed players
     */
    public function packedPlayerCount(): int
    {
        return $this->players->where('packId', '!=', null)->count();
    }

    /**
     * Check if this realm contains a draft pack
     *
     * A draft pack is a player-organized group that exceeds MAX_PACKED_PLAYERS_PER_REALM,
     * submitted via an external draft event. Such realms should be excluded from normal
     * solo player assignment and size balancing.
     *
     * @return bool True if this realm's packed player count exceeds the cap
     */
    public function isDraftPackRealm(): bool
    {
        return $this->packedPlayerCount() > RealmAssignmentService::MAX_PACKED_PLAYERS_PER_REALM;
    }

    /**
     * Check if a pack can fit in this realm
     *
     * Validates that adding the pack would not exceed the maximum number
     * of packed players allowed per realm. Solo players don't count toward
     * this limit.
     *
     * @param PlaceholderPack $pack The pack to check for fit
     * @return bool True if the pack can fit within the packed player limit
     */
    public function canFitPack(PlaceholderPack $pack): bool
    {
        return $this->packedPlayerCount() + $pack->size <= RealmAssignmentService::MAX_PACKED_PLAYERS_PER_REALM;
    }

    /**
     * Add an entire pack to this realm
     *
     * Adds all members of a pack to the realm's player collection and
     * updates the realm's derived statistics (size and rating).
     *
     * @param PlaceholderPack $pack The pack to add to this realm
     */
    public function addPack(PlaceholderPack $pack): void
    {
        foreach ($pack->members as $player) {
            $this->players->put($player->id, $player);
        }
        $this->update();
    }

    /**
     * Add a single player to this realm
     *
     * Adds a player to the realm's player collection and updates the
     * realm's derived statistics (size and rating).
     *
     * @param Player $player The player to add to this realm
     */
    public function addPlayer(Player $player): void
    {
        $this->players->put($player->id, $player);
        $this->update();
    }

    /**
     * Update realm's derived statistics
     *
     * Recalculates the realm's size (player count) and average rating
     * across all players. Called whenever players are added
     * or removed from the realm.
     */
    public function update(): void
    {
        $this->size = $this->players->count();
        $this->rating = $this->players->avg('rating') ?? 0;
    }

    /**
     * Calculate compatibility score for adding players to this realm
     *
     * Combines favorability between players with playstyle fit. Favorability is summed
     * per pair across both directions, bounded by FAVORABILITY_PAIR_LIMIT so that a long
     * history between two players cannot outweigh the rest of the realm, then weighted
     * asymmetrically: positive feedback is a mild clustering nudge, negative feedback is a
     * strong deterrent. See the constants for the measurements behind each bound.
     *
     * @param Collection $players Collection of Player objects to evaluate
     * @return float Total compatibility score (higher is better)
     */
    public function getCompatibilityScore(Collection $players): float
    {
        $positiveScore = 0;
        $negativeScore = 0;

        foreach ($players as $newMember) {
            foreach ($this->players as $realmMember) {
                // Both directions belong to one relationship, so the limit applies to
                // their sum. Bounding each direction separately would let a mutual pair
                // reach twice the intended ceiling.
                $pairScore = $realmMember->getFavorabilityWith($newMember->userId)
                    + $newMember->getFavorabilityWith($realmMember->userId);

                $pairScore = max(
                    -static::FAVORABILITY_PAIR_LIMIT,
                    min(static::FAVORABILITY_PAIR_LIMIT, $pairScore)
                );

                if ($pairScore > 0) {
                    $positiveScore += $pairScore;
                } else {
                    $negativeScore += $pairScore;
                }
            }
        }

        return min($positiveScore, static::FAVORABILITY_POSITIVE_LIMIT) * static::FAVORABILITY_POSITIVE_WEIGHT
            + $negativeScore * static::FAVORABILITY_NEGATIVE_WEIGHT
            + $this->calculatePlaystyleScore($players);
    }

    /**
     * Calculate playstyle score for adding players to this realm
     *
     * Evaluates how adding players would move the realm closer to or further from
     * the ideal number of players capable of each playstyle. Players with unknown
     * affinities are excluded from both the specialist counts and the targets those
     * counts are measured against.
     *
     * @param Collection $players Players to evaluate for addition
     * @return float Balance improvement score (positive is better)
     */
    public function calculatePlaystyleScore(Collection $players): float
    {
        $knownIncomingPlayers = $this->knownAffinityPlayers($players);
        if ($knownIncomingPlayers->isEmpty()) {
            return 0.0;
        }

        $knownCurrentPlayers = $this->knownAffinityPlayers($this->players);
        if ($knownCurrentPlayers->isEmpty()) {
            // Nothing to improve on. Scoring an empty baseline would report the
            // maximum possible improvement and swamp the size and rating terms.
            return 0.0;
        }

        $currentDeviation = $this->calculatePlaystyleDeviation($knownCurrentPlayers);
        $projectedDeviation = $this->calculatePlaystyleDeviation(
            $knownCurrentPlayers->concat($knownIncomingPlayers)
        );

        return ($currentDeviation - $projectedDeviation) * static::PLAYSTYLE_SCORE_WEIGHT;
    }

    /**
     * Get realm's current playstyle composition as averages
     *
     * Reported in assignment statistics only. Scoring uses specialist counts instead,
     * because averaging affinities across a realm cannot tell a realm of specialists
     * apart from a realm of generalists — both average out to the same figures.
     *
     * @return array Associative array with playstyle averages (attackerAffinity, converterAffinity, etc.)
     */
    public function getPlaystyleComposition(): array
    {
        return $this->getPlaystyleCompositionFor($this->players);
    }

    /**
     * Count how many of the given players are capable of each playstyle
     *
     * A player counts toward a playstyle once their affinity reaches
     * SPECIALIST_THRESHOLD. Playstyles are independent, so one player can count
     * toward several at once.
     *
     * Players with unknown affinities are filtered out here rather than by the caller:
     * User::getAffinity() falls back to 50 for missing data, which clears the threshold
     * and would silently count every unrated player as an attacker and an explorer.
     *
     * @param Collection $players Players to count
     * @return array Associative array of playstyle => number of capable players
     */
    public function getSpecialistCounts(Collection $players): array
    {
        $knownPlayers = $this->knownAffinityPlayers($players);
        $counts = [];

        foreach (array_keys(static::IDEAL_COMPOSITION) as $style) {
            $counts[$style] = $knownPlayers->filter(function (Player $player) use ($style): bool {
                return $player->$style >= static::SPECIALIST_THRESHOLD;
            })->count();
        }

        return $counts;
    }

    /**
     * Report how far this realm's specialist mix sits from ideal
     *
     * The same figure the assignment scoring minimises, exposed for dry-run statistics.
     * Zero means every playstyle is represented in its target proportion.
     */
    public function getPlaystyleDeviation(): float
    {
        return $this->calculatePlaystyleDeviation($this->players);
    }

    /**
     * Calculate how far a set of players deviates from the ideal specialist mix
     *
     * Deviations are squared rather than absolute. Absolute deviation is linear below
     * the target, so it scores an even split of a scarce playstyle identically to a
     * stacked one — 3+1 and 2+2 both cost 3.20 against a target of 3.6. That is exactly
     * the decision the assignment and swap passes make, and scarce playstyles sit below
     * target almost always. Squaring makes the even split win.
     *
     * @param Collection $players Players with known affinities
     * @return float Total squared deviation from ideal (lower is better)
     */
    private function calculatePlaystyleDeviation(Collection $players): float
    {
        $specialistCounts = $this->getSpecialistCounts($players);
        $playerCount = $this->knownAffinityPlayers($players)->count();
        $totalDeviation = 0.0;

        foreach (static::IDEAL_COMPOSITION as $style => $idealPercentage) {
            $target = $idealPercentage / 100 * $playerCount;
            $totalDeviation += ($specialistCounts[$style] - $target) ** 2;
        }

        return $totalDeviation;
    }

    private function getPlaystyleCompositionFor(Collection $players): array
    {
        $knownPlayers = $this->knownAffinityPlayers($players);

        if ($knownPlayers->isEmpty()) {
            return array_fill_keys(array_keys(static::IDEAL_COMPOSITION), 0.0);
        }

        $composition = [];
        foreach (array_keys(static::IDEAL_COMPOSITION) as $style) {
            $composition[$style] = (float) $knownPlayers->avg($style);
        }

        return $composition;
    }

    private function knownAffinityPlayers(Collection $players): Collection
    {
        return $players->filter(function (Player $player): bool {
            return $player->hasKnownAffinities;
        });
    }
}

/**
 * Main Realm Assignment Service
 */
class RealmAssignmentService
{
    /**
     * @var int Maximum number of packs that can exist in a single realm
     */
    public const MAX_PACKS_PER_REALM = 3;

    /**
     * @var int Maximum number of players allowed in packs in a single realm
     */
    public const MAX_PACKED_PLAYERS_PER_REALM = 8;

    /**
     * @var int Number of hours before round start to begin realm assignment
     */
    public const ASSIGNMENT_HOURS_BEFORE_START = 96;

    /**
     * @var float Rating at or below which a player is treated as new
     *
     * User::getEffectiveRating() resolves an unrated user to User::DEFAULT_RATING, so
     * the boundary must be inclusive — a player sitting exactly on it has no rating
     * history to place them by. Comparisons against this value must be `<=` for new
     * and `>` for experienced, so unrated players land on one side everywhere.
     */
    public const NEW_PLAYER_RATING = User::DEFAULT_RATING;

    /**
     * @var int Minimum number of realms to create
     */
    public const ASSIGNMENT_MIN_REALM_COUNT = 8;

    /**
     * @var int Maximum number of realms to create
     */
    public const ASSIGNMENT_MAX_REALM_COUNT = 14;

    /**
     * @var float Calculate what an average realm's stats should be
     */
    public float $targetRealmSize = 0.0;
    public float $targetRealmStrength = 0.0;

    public Collection $packs;

    public Collection $players;

    public Collection $realms;

    public Collection $nonDiscordRealms;

    public Collection $draftPackRealms;

    /**
     * Constructor - initialize collections
     */
    public function __construct()
    {
        $this->players = collect();
        $this->packs = collect();
        $this->realms = collect();
        $this->nonDiscordRealms = collect();
        $this->draftPackRealms = collect();
        // Target values will be calculated dynamically when needed
        $this->targetRealmSize = 12;
        $this->targetRealmStrength = 1500;
    }

    /**
     * Get total count of all realms (Discord + non-Discord)
     */
    public function getRealmCount(): int
    {
        return $this->realms->count() + $this->nonDiscordRealms->count();
    }

    /**
     * Assigns all registered dominions (in realm 0) to newly created realms
     *
     * This is the main entry point for the realm assignment algorithm. It orchestrates
     * the entire process: closing packs, loading players, calculating optimal realm
     * structure, assigning packs and solo players, and performing post-assignment
     * optimization. Returns the final realm assignments.
     *
     * @param Round $round The round to perform realm assignment for
     * @param bool $dryRun If true, skips database creation and outputs stats to console
     * @return Collection Collection of PlaceholderRealm objects with assigned players
     */
    public function assignRealms(Round $round, bool $dryRun = false)
    {
        if (!$dryRun) {
            $this->closePacks($round);
        }

        $this->loadPlayers($round);
        $nonDiscordPlayerCount = $this->players->where('hasDiscord', false)->where('packId', null)->count();
        $discordPlayerCount = $this->players->count() - $nonDiscordPlayerCount;

        // Calculate targets based on Discord players only
        $this->targetRealmStrength = $this->players->reject(function ($player) {
            return !$player->hasDiscord && $player->packId === null;
        })->avg('rating') ?: 1500;

        $this->loadPacks();

        // Separate non-Discord players early, before any calculations
        $this->createNonDiscordRealms();

        // Assign large packs. calculateRealmCount() has already subtracted the non-Discord
        // realms created above from both the minimum and the maximum.
        $discordRealmCount = $this->calculateRealmCount();
        $this->createPlaceholderRealms($discordRealmCount);

        // Draft-pack realms are set aside by createPlaceholderRealms() and take no part in
        // solo assignment or size balancing, so neither they nor their members belong in the
        // size target. Counting them left the target too high for the realms that remain.
        $assignableRealmCount = $this->realms->count();
        $assignablePlayerCount = $discordPlayerCount - $this->draftPackRealms->sum('size');
        $this->targetRealmSize = $assignableRealmCount > 0
            ? floor($assignablePlayerCount / $assignableRealmCount)
            : 0;

        // Assign small packs
        $this->assignPacks();

        // Assign solos
        $this->assignSolos();

        // Optimization pass
        $this->optimizeAssignments();

        if ($dryRun) {
            // Output comprehensive assignment statistics
            return $this->getAssignmentStats();
        }

        // Create the final realms
        $this->createRealms($round);

        // Send assignment notifications
        $this->sendNotifications($round);
    }

    /**
     * Close all packs for the round and unlink solo players
     *
     * Finalizes all pack registrations by closing them and calculating their
     * final ratings. Packs with only one member are dissolved and that player
     * becomes a solo player instead.
     *
     * @param Round $round The round to close packs for
     */
    public function closePacks(Round $round): void
    {
        // Close open packs and unlink solo players
        $packs = Pack::where('round_id', $round->id)->get();
        foreach ($packs as $pack) {
            $pack->close();
            if ($pack->dominions()->count() == 1) {
                $pack->dominions()->update(['pack_id' => null]);
            }
        }
    }

    /**
     * Load all registered players for the round
     *
     * Fetches all registered dominions from the database and converts them
     * to Player objects with favorability matrices, effective ratings,
     * playstyle affinities, and other assignment-relevant data.
     *
     * @param Round $round The round to load players for
     */
    public function loadPlayers(Round $round): void
    {
        // Fetch all registered dominions
        $registeredDominions = $round->activeDominions()
            ->human()
            ->with('user')
            ->where('realms.number', 0)
            ->get();

        // Fetch favorability data
        $userIds = $registeredDominions->pluck('user_id');
        $userFeedback = UserFeedback::whereIn('source_id', $userIds)->get();

        // Collect data for all dominions
        foreach ($registeredDominions as $dominion) {
            $playerFeedback = $userFeedback->where('source_id', $dominion->user_id);
            $favorabilityMatrix = $playerFeedback->groupBy('target_id')->mapWithKeys(function ($feedbacks, $targetId) {
                $positive = $feedbacks->where('endorsed', true)->count();
                $negative = $feedbacks->where('endorsed', false)->count();
                return [$targetId => $positive - $negative];
            })->toArray();
            // Determine Discord preference - false only if setting exists and is explicitly false
            $discordSetting = $dominion->getSetting('usediscord');
            $hasDiscord = !($discordSetting !== null && $discordSetting === false);

            // Create player
            $player = Player::fromUser($dominion->user, [
                'id' => $dominion->id,
                'userId' => $dominion->user_id,
                'packId' => $dominion->pack_id,
                'favorability' => $favorabilityMatrix,
                'hasDiscord' => $hasDiscord,
            ]);
            $this->players->put($dominion->id, $player);
        }
    }

    /**
     * Create Pack objects from players
     *
     * Groups packed players by their pack ID and creates PlaceholderPack objects.
     * Removes packed players from the solo players collection since they'll be
     * assigned as part of their pack rather than individually.
     */
    public function loadPacks(): void
    {
        $playersByPack = $this->players
            ->whereNotNull('packId')
            ->groupBy('packId');

        foreach ($playersByPack as $packId => $packMembers) {
            $pack = new PlaceholderPack($packId, $packMembers);
            $this->packs->put($packId, $pack);
            foreach ($packMembers->pluck('id') as $playerId) {
                $this->players->forget($playerId);
            }
        }
    }

    /**
     * Calculate optimal number of Discord realms based on pack sizes and constraints
     *
     * The number of realms is primarily determined by the number of large packs
     * (>3 players), with adjustments to stay within min/max bounds. Takes into account
     * existing non-Discord realms to ensure total realm count doesn't exceed limits.
     *
     * @return int Number of Discord realms to create
     */
    public function calculateRealmCount(): int
    {
        $largePacks = $this->packs->where('large', true)->count();
        $totalPackedPlayers = $this->packs->sum('size');
        $packedHeadroomRealms = (int) ceil($totalPackedPlayers / self::MAX_PACKED_PLAYERS_PER_REALM);

        $nonDiscordRealmCount = $this->nonDiscordRealms->count();
        $maxDiscordRealms = self::ASSIGNMENT_MAX_REALM_COUNT - $nonDiscordRealmCount;
        $minDiscordRealms = max(0, self::ASSIGNMENT_MIN_REALM_COUNT - $nonDiscordRealmCount);

        // Need enough realms to seed every large pack AND enough packed-player
        // headroom (each realm caps at MAX_PACKED_PLAYERS_PER_REALM) to absorb
        // every packed player without overflowing.
        $desiredRealms = max($largePacks, $packedHeadroomRealms);

        // The trailing 1 keeps at least one Discord realm alive even when non-Discord
        // realms have already consumed ASSIGNMENT_MAX_REALM_COUNT, which would otherwise
        // leave createPlaceholderRealms() building nothing for the remaining players.
        $targetRealms = max($minDiscordRealms, min($maxDiscordRealms, $desiredRealms), 1);

        if ($largePacks > $targetRealms) {
            $this->downgradePacks($largePacks - $targetRealms);
        } elseif ($largePacks < $targetRealms) {
            $this->upgradePacks($targetRealms - $largePacks);
        }

        return $targetRealms;
    }

    /**
     * Downgrade large packs to small packs
     *
     * Selects the lowest-rated large packs and marks them as small packs.
     * This is done when there are too many large packs to stay within
     * the maximum realm count.
     *
     * @param int $count Number of packs to downgrade
     */
    public function downgradePacks(int $count): void
    {
        $packs = $this->packs->where('large', true)->sortBy('rating')->take($count);
        foreach ($packs as $pack) {
            $this->packs[$pack->id]->large = false;
        }
    }

    /**
     * Upgrade small packs to large packs
     *
     * Selects the highest-rated small packs and marks them as large packs.
     * This is done when there are too few large packs to meet the minimum
     * realm count requirement.
     *
     * @param int $count Number of packs to upgrade
     */
    public function upgradePacks(int $count): void
    {
        $packs = $this->packs->where('large', false)->sortByDesc('rating')->take($count);
        foreach ($packs as $pack) {
            $this->packs[$pack->id]->large = true;
        }
    }

    /**
     * Create initial Discord-enabled realms from packs
     *
     * Builds exactly $targetRealmCount Discord realms: one seeded from each large pack,
     * topped up from small packs and then with empty realms. Non-Discord realms are created
     * separately and are already subtracted from the target by calculateRealmCount().
     *
     * The count has to come from the caller rather than being re-derived here. This method
     * previously topped up against ASSIGNMENT_MIN_REALM_COUNT using an inclusive range(),
     * which both ignored the count assignRealms() had already calculated and overshot it by
     * one — eight realms were requested and nine were built. targetRealmSize is derived from
     * the requested count, so the extra realm left the size target too high and the
     * over-target penalty in calculateSizeBonus() never engaged.
     *
     * @param int $targetRealmCount Number of Discord realms to create, including any that
     *                              turn out to hold a draft pack
     */
    public function createPlaceholderRealms(int $targetRealmCount): void
    {
        // Step 1: Seed one realm per large pack
        $largePacks = $this->packs->where('large', true);
        foreach ($largePacks as $idx => $pack) {
            $realm = new PlaceholderRealm("large-{$idx}", $pack->members);
            $this->realms->push($realm);
            $this->packs->forget($pack->id);
        }

        // Step 2: Use small packs to seed any realms the large packs did not cover
        $smallPacks = $this->packs->where('large', false);
        foreach ($smallPacks as $idx => $pack) {
            if ($this->realms->count() >= $targetRealmCount) {
                break;
            }
            $realm = new PlaceholderRealm("small-{$idx}", $pack->members);
            $this->realms->push($realm);
            $this->packs->forget($pack->id);
        }

        // Step 3: Fill the remainder with empty realms
        while ($this->realms->count() < $targetRealmCount) {
            $realm = new PlaceholderRealm('solo-' . $this->realms->count(), collect());
            $this->realms->push($realm);
        }

        // Step 4: Separate draft pack realms so solo assignment and balancing ignore them
        $this->draftPackRealms = $this->realms->filter(function ($realm) {
            return $realm->isDraftPackRealm();
        });
        $this->realms = $this->realms->reject(function ($realm) {
            return $realm->isDraftPackRealm();
        })->values();
    }

    /**
     * Create non-Discord realms for solo players who opted out of Discord
     *
     * Creates a single non-Discord realm unless there are more than 12
     * non-Discord solo players, in which case additional realms are created.
     * Stores them separately to avoid interfering with main assignment logic.
     */
    public function createNonDiscordRealms(): void
    {
        $nonDiscordSoloPlayers = $this->players->where('hasDiscord', false);
        $totalNonDiscordPlayers = $nonDiscordSoloPlayers->count();

        if ($totalNonDiscordPlayers === 0) {
            return; // No non-Discord players, nothing to do
        }

        // Determine number of non-Discord realms needed (prefer 1, but max 14 players per realm)
        $nonDiscordRealmCount = max(1, ceil($totalNonDiscordPlayers / 14));

        // Create non-Discord realms with integer IDs starting from 0
        foreach (range(1, $nonDiscordRealmCount) as $idx) {
            $realm = new PlaceholderRealm("non-discord-{$idx}", collect(), false);
            $this->nonDiscordRealms->push($realm);
        }

        // Assign non-Discord solo players to non-Discord realms (round-robin)
        foreach ($nonDiscordSoloPlayers->values() as $index => $player) {
            $realmIndex = $index % $nonDiscordRealmCount;
            $this->nonDiscordRealms[$realmIndex]->addPlayer($player);
            $this->players->forget($player->id);
        }
    }

    /**
     * Assign all remaining packs to realms
     *
     * Orchestrates the assignment of non-large packs to existing realms
     * using the sophisticated scoring algorithm. Each pack is removed from
     * the assignment queue after placement.
     */
    public function assignPacks(): void
    {
        foreach ($this->packs as $pack) {
            $this->assignPack($pack);
            $this->packs->forget($pack->id);
        }
    }

    /**
     * Assign a single pack to the best available realm
     *
     * Evaluates all realms that can fit the pack and selects the one with the
     * highest placement score. The scoring considers compatibility, balance,
     * and opportunity cost. If no realm meets size constraints, the best
     * overall realm is chosen regardless of size limits.
     *
     * @param PlaceholderPack $pack The pack to assign to a realm
     */
    public function assignPack(PlaceholderPack $pack): void
    {
        $bestRealm = null;
        $bestScore = -INF;

        $potentialRealms = $this->realms->filter(function ($realm) use ($pack) {
            return $realm->canFitPack($pack);
        });
        if ($potentialRealms->isEmpty()) {
            // Ignore size restrictions if no other options
            $potentialRealms = $this->realms->where('rating', '<', $this->targetRealmStrength);
        }
        if ($potentialRealms->isEmpty()) {
            // Last resort: every realm is over the packed-player cap AND over target rating.
            // Let the scoring (size penalty + balance score) pick the least bad option.
            $potentialRealms = $this->realms;
        }

        foreach ($potentialRealms as $realm) {
            $score = $this->evaluatePackPlacement($pack, $realm);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRealm = $realm;
            }
        }

        $bestRealm->addPack($pack);
    }

    /**
     * Evaluate placing pack in existing realm
     *
     * Calculates a comprehensive score for placing a pack in a specific realm.
     * The score combines compatibility (favorability + playstyle), balance
     * improvement, and opportunity cost considerations. Returns -INF for
     * placements that would create severe conflicts.
     *
     * @param PlaceholderPack $pack The pack to evaluate
     * @param PlaceholderRealm $realm The realm to evaluate placement in
     * @return float Placement score (higher is better, -INF for conflicts)
     */
    public function evaluatePackPlacement(
        PlaceholderPack $pack,
        PlaceholderRealm $realm
    ): float {
        $placementScore = $this->calculatePlacementScore($realm, $pack->members);

        // Opportunity cost compares two packs competing for the SAME realm, so both
        // would collect that realm's identical size bonus and it says nothing about
        // which pack deserves the slot. Comparing a size-inclusive current score
        // against size-free rival scores left the bonus behind on every iteration,
        // scaling it by however many rival packs happened to fit — a realm with more
        // pack headroom had its size term multiplied by (1 + 0.3 * rivals).
        $opportunityCost = $this->calculateOpportunityCost(
            $realm,
            $pack,
            $this->calculatePlacementScore($realm, $pack->members, false)
        );

        return $placementScore + $opportunityCost;
    }

    /**
     * Calculate rating balance improvement from adding players
     *
     * Measures how much adding the given players would improve the realm's
     * deviation from the target strength. Returns positive values when the
     * addition brings the realm closer to the target, negative when it moves
     * further away. Used to encourage balanced realm strengths.
     *
     * @param PlaceholderRealm $realm The realm to evaluate
     * @param Collection $players The players to potentially add
     * @return float Balance improvement score (positive is better)
     */
    public function calculateBalanceScore(PlaceholderRealm $realm, Collection $players): float
    {
        if ($realm->players->count() == 0) {
            return 0;
        }

        $currentRating = $realm->players->sum('rating');
        $currentAverageRating = $currentRating / $realm->players->count();
        $currentDeviation = abs($this->targetRealmStrength - $currentAverageRating);

        $newRating = $currentRating + $players->sum('rating');
        $newAverageRating = $newRating / ($realm->players->count() + $players->count());
        $newDeviation = abs($this->targetRealmStrength - $newAverageRating);

        // Reward improvement, penalize making things worse
        $improvement = $currentDeviation - $newDeviation;

        if ($improvement > 0) {
            // Adding this player improves balance - reward it
            return $improvement * 2; // 2x multiplier for improvements
        } else {
            // Adding this player makes balance worse - penalize it
            return $improvement * 3; // 3x penalty for making things worse
        }
    }

    /**
     * Calculate opportunity cost of this placement
     *
     * Evaluates whether other unassigned packs could make better use of this realm.
     * The opportunity cost is higher when other packs would score better in this
     * realm AND have fewer viable alternatives. This encourages leaving realms
     * available for packs that need them most.
     *
     * @param PlaceholderRealm $realm The realm being considered
     * @param PlaceholderPack $pack The pack being placed
     * @param float $currentPackScore The score this pack would achieve
     * @return float Opportunity cost (negative values discourage placement)
     */
    public function calculateOpportunityCost(
        PlaceholderRealm $realm,
        PlaceholderPack $pack,
        float $currentPackScore
    ): float {
        $opportunityCost = 0;

        // Look at other unassigned packs that could use this realm
        foreach ($this->packs as $otherPack) {
            $otherPackScore = 0;

            if ($otherPack->id === $pack->id) {
                continue; // Skip the current pack
            }

            // Could this other pack fit in this realm?
            if (!$realm->canFitPack($otherPack)) {
                continue; // Can't fit, so no opportunity cost
            }

            $otherPackScore += $realm->getCompatibilityScore($otherPack->members);
            $otherPackScore += $this->calculateBalanceScore($realm, $otherPack->members);

            $opportunityCost -= ($otherPackScore - $currentPackScore) * 0.3;
        }

        return $opportunityCost;
    }

    /**
     * Calculate size bonus/penalty for realm assignments
     *
     * Scored on the size the realm would have *after* the placement, so a pack that
     * would overshoot the target is penalised before it lands rather than on the next
     * placement. Realms below target receive a small bonus proportional to how many
     * players they still need.
     *
     * The over-target penalty is large enough to remove a realm from contention —
     * it dominates realistic compatibility and balance scores by an order of
     * magnitude — but it grows with the overshoot rather than being a flat cliff.
     * targetRealmSize is floor(players / realms), so whenever players do not divide
     * evenly some realms must exceed the target; a flat penalty made a realm of 40
     * indistinguishable from one of 13 and let placements land on the worst of them.
     *
     * @param PlaceholderRealm $realm The realm to evaluate
     * @param int $incomingPlayers How many players the placement would add
     * @return float Size bonus/penalty (large negative when over target)
     */
    public function calculateSizeBonus(PlaceholderRealm $realm, int $incomingPlayers): float
    {
        $projectedSize = $realm->players->count() + $incomingPlayers;
        $targetSize = $this->targetRealmSize;

        if ($projectedSize > $targetSize) {
            return -5000 - ($projectedSize - $targetSize) * 100;
        }

        // Moderate bonus for realms under ideal size
        return ($targetSize - $projectedSize) * 10;
    }

    /**
     * Calculate comprehensive placement score combining all factors
     *
     * Combines compatibility (favorability + playstyle), rating balance, and size
     * constraints into a single weighted score. This ensures consistent scoring
     * across both assignment and optimization phases.
     *
     * @param PlaceholderRealm $realm The realm to evaluate
     * @param Collection $players The players to potentially add
     * @param bool $includeSize Whether to include size bonus in calculation (default true)
     * @return float Total placement score (higher is better)
     */
    public function calculatePlacementScore(PlaceholderRealm $realm, Collection $players, bool $includeSize = true): float
    {
        $compatibilityScore = $realm->getCompatibilityScore($players);
        $balanceScore = $this->calculateBalanceScore($realm, $players);
        $sizeBonus = $includeSize ? $this->calculateSizeBonus($realm, $players->count()) : 0;

        return $compatibilityScore + $balanceScore + $sizeBonus;
    }

    /**
     * Assign solo players to realms
     *
     * Orchestrates the assignment of individual players in two phases:
     * Phase 1 distributes new players (at or below NEW_PLAYER_RATING) evenly using
     * round-robin. Phase 2 assigns experienced players using full scoring with size
     * constraints. This ensures fair distribution while optimizing for compatibility
     * and balance.
     */
    public function assignSolos(): void
    {
        // Phase 1: Distribute new players using round-robin
        $newPlayers = $this->players->where('rating', '<=', self::NEW_PLAYER_RATING)->values(); // Get indexed collection
        $realmCount = $this->realms->count();

        // Assign all new players using round-robin across realms
        foreach ($newPlayers as $index => $newPlayer) {
            $realmIndex = ($index % $realmCount);
            $realm = $this->realms[$realmIndex];
            $realm->addPlayer($newPlayer);
            $this->players->forget($newPlayer->id);
        }

        // Phase 2: Assign experienced players using full scoring
        $this->assignExperiencedPlayers();
    }

    /**
     * Assign experienced players using full scoring system
     *
     * Assigns whichever players assignSolos() did not round-robin — those above
     * NEW_PLAYER_RATING — using comprehensive scoring that considers compatibility,
     * balance, and size constraints. Players are sorted by rating (highest first)
     * for strategic placement. Hard conflicts are avoided and the size penalty
     * ensures equal distribution across realms.
     */
    public function assignExperiencedPlayers(): void
    {
        // Sort by rating (highest first) for strategic placement
        $sortedPlayers = $this->players->sortByDesc('rating')->values();

        foreach ($sortedPlayers as $player) {
            $bestRealm = null;
            $bestScore = -INF;

            foreach ($this->realms as $realm) {
                // Calculate comprehensive placement score
                $totalScore = $this->calculatePlacementScore($realm, collect([$player]));

                if ($totalScore > $bestScore) {
                    $bestScore = $totalScore;
                    $bestRealm = $realm;
                }
            }

            if ($bestRealm) {
                $bestRealm->addPlayer($player);
                $this->players->forget($player->id);
            }
        }
    }

    /**
     * Balance realm sizes by moving solo players from largest to smallest realms
     *
     * Performs simple size balancing before optimization by moving solo players
     * from the largest realm to the smallest realm until sizes are within ±1.
     * This ensures good size balance before compatibility optimization begins.
     */
    public function balanceRealmSizes(): void
    {
        $maxIterations = 10; // Prevent infinite loops
        $iterations = 0;

        while ($iterations < $maxIterations) {
            // Sort realms by size
            $sortedBySize = $this->realms->sortBy('size');
            $smallest = $sortedBySize->first();
            $largest = $sortedBySize->last();

            // Stop if sizes are balanced (difference ≤ 1)
            if ($largest->size - $smallest->size <= 1) {
                break;
            }

            // Find the best solo player to move (one that improves or least worsens rating balance)
            // Prefer experienced players, but fall back to any available solo player
            $availablePlayers = $largest->soloPlayers()->where('rating', '>', self::NEW_PLAYER_RATING);
            if ($availablePlayers->isEmpty()) {
                $availablePlayers = $largest->soloPlayers();
            }
            if ($availablePlayers->isEmpty()) {
                break; // No solo players at all in largest realm, cannot balance further
            }

            $bestPlayer = null;
            $bestBalanceImpact = -INF;

            foreach ($availablePlayers as $player) {
                // Calculate rating balance impact of moving this player
                $currentLargestAvg = $largest->players->avg('rating');
                $currentSmallestAvg = $smallest->players->avg('rating');

                $newLargestAvg = $largest->players->where('id', '!=', $player->id)->avg('rating');
                $newSmallestAvg = ($smallest->players->sum('rating') + $player->rating) / ($smallest->players->count() + 1);

                // Calculate improvement in balance (lower total deviation is better)
                $currentDeviation = abs($this->targetRealmStrength - $currentLargestAvg) + abs($this->targetRealmStrength - $currentSmallestAvg);
                $newDeviation = abs($this->targetRealmStrength - $newLargestAvg) + abs($this->targetRealmStrength - $newSmallestAvg);
                $balanceImpact = $currentDeviation - $newDeviation; // Positive = improvement

                if ($balanceImpact > $bestBalanceImpact) {
                    $bestBalanceImpact = $balanceImpact;
                    $bestPlayer = $player;
                }
            }

            if (!$bestPlayer) {
                break; // No suitable player found
            }

            // Move the best player from largest to smallest realm
            $largest->players->forget($bestPlayer->id);
            $largest->update(); // Recalculate size and rating
            $smallest->addPlayer($bestPlayer);
            $smallest->update(); // Ensure smallest realm is also updated

            $iterations++;
        }
    }

    /**
     * Post-assignment optimization through randomized player swapping
     *
     * Performs iterative optimization by randomly sampling pairs of solo players
     * from different realms and swapping them if beneficial. This approach is more
     * efficient than exhaustive search and better explores the solution space.
     * Runs for up to 15 iterations or until no more improvements are found.
     */
    public function optimizeAssignments(): void
    {
        // Pre-balancing: Move solo players from largest to smallest realms to even out sizes
        $this->balanceRealmSizes();

        $improved = true;
        $iterations = 0;
        $maxIterations = 50;
        $totalSwaps = 0;
        $samplesPerIteration = 25; // Number of random pairs to test per iteration

        while ($improved && $iterations < $maxIterations) {
            $improved = false;
            $iterations++;

            // Collect all solo players with their realm assignments
            $soloPlayers = collect();
            foreach ($this->realms as $realm) {
                foreach ($realm->soloPlayers()->where('rating', '>', self::NEW_PLAYER_RATING) as $player) {
                    $soloPlayers->push([
                        'player' => $player,
                        'realm' => $realm
                    ]);
                }
            }

            // Skip if not enough solo players to swap
            if ($soloPlayers->count() < 2) {
                break;
            }

            // Random sampling approach - test fixed number of random pairs
            for ($sample = 0; $sample < $samplesPerIteration; $sample++) {
                // Randomly select two different players from different realms
                $attempts = 0;
                do {
                    $player1Data = $soloPlayers->random();
                    $player2Data = $soloPlayers->random();
                    $attempts++;
                } while ($player1Data['realm']->id === $player2Data['realm']->id && $attempts < 10);

                // Skip if we couldn't find players from different realms
                if ($player1Data['realm']->id === $player2Data['realm']->id) {
                    continue;
                }

                $solo1 = $player1Data['player'];
                $solo2 = $player2Data['player'];
                $realm1 = $player1Data['realm'];
                $realm2 = $player2Data['realm'];

                if ($this->shouldSwapSolos($solo1, $solo2, $realm1, $realm2)) {
                    // Perform swap by removing and adding players
                    $realm1->players->forget($solo1->id);
                    $realm2->players->forget($solo2->id);

                    $realm1->players->put($solo2->id, $solo2);
                    $realm2->players->put($solo1->id, $solo1);

                    // Update realm state
                    $realm1->update();
                    $realm2->update();

                    $improved = true;
                    $totalSwaps++;

                    // Update our tracking collection to reflect the swap
                    $soloPlayers = $soloPlayers->map(function ($data) use ($solo1, $solo2, $realm1, $realm2) {
                        if ($data['player']->id === $solo1->id) {
                            return ['player' => $solo1, 'realm' => $realm2];
                        } elseif ($data['player']->id === $solo2->id) {
                            return ['player' => $solo2, 'realm' => $realm1];
                        }
                        return $data;
                    });
                }
            }
        }

        // Merge non-Discord realms into main realms collection so downstream functions see all realms
        $this->realms = $this->realms->merge($this->draftPackRealms)->merge($this->nonDiscordRealms);
    }

    /**
     * Check if swapping two solos would improve overall balance
     *
     * Evaluates whether swapping two solo players between realms would improve
     * the total assignment score. Checks for hard conflicts first, then compares
     * current vs post-swap scores using compatibility and balance metrics.
     * Includes a small threshold to prevent oscillating swaps.
     *
     * @param Player $solo1 First player to potentially swap
     * @param Player $solo2 Second player to potentially swap
     * @param PlaceholderRealm $realm1 Current realm of first player
     * @param PlaceholderRealm $realm2 Current realm of second player
     * @return bool True if the swap would improve overall assignment quality
     */
    public function shouldSwapSolos(
        Player $solo1,
        Player $solo2,
        PlaceholderRealm $realm1,
        PlaceholderRealm $realm2
    ): bool {
        // 1. Calculate accurate swap scores by removing players first, then evaluating placements

        // Remove players temporarily to get accurate base scores
        $realm1->players->forget($solo1->id);
        $realm2->players->forget($solo2->id);
        $realm1->update();
        $realm2->update();

        // Calculate base scores without either player (excluding size - focus on compatibility/balance only)
        $currentScore1 = $this->calculatePlacementScore($realm1, collect([$solo1]), false);
        $currentScore2 = $this->calculatePlacementScore($realm2, collect([$solo2]), false);
        $currentTotal = $currentScore1 + $currentScore2;

        // Calculate post-swap scores (excluding size - focus on compatibility/balance only)
        $newScore1 = $this->calculatePlacementScore($realm1, collect([$solo2]), false);
        $newScore2 = $this->calculatePlacementScore($realm2, collect([$solo1]), false);
        $newTotal = $newScore1 + $newScore2;

        // Restore players to their original realms
        $realm1->players->put($solo1->id, $solo1);
        $realm2->players->put($solo2->id, $solo2);
        $realm1->update();
        $realm2->update();

        $improvement = $newTotal - $currentTotal;

        // 4. Only swap if there's meaningful improvement (threshold prevents oscillation)
        return $improvement > 0.1;
    }

    /**
     * Create actual realms from placeholder assignments and persist to database
     *
     * Converts the balanced placeholder realm assignments into actual Realm entities,
     * assigns dominions to their designated realms, and updates pack affiliations.
     * Also sets realm ratings based on the calculated balance scores and marks
     * the round as assignment complete when all dominions are properly assigned.
     *
     * @param Round $round The game round to create realms for
     * @return void
     */
    public function createRealms(Round $round): void
    {
        $realmFactory = app(RealmFactory::class);

        foreach ($this->realms->shuffle() as $placeholderRealm) {
            $realm = $realmFactory->create($round);

            // Store Discord preference in realm settings
            $realm->settings = array_merge($realm->settings ?? [], [
                'usediscord' => $placeholderRealm->discordEnabled
            ]);

            foreach ($placeholderRealm->players as $player) {
                $dominion = Dominion::find($player->id);
                $dominion->realm_id = $realm->id;
                $dominion->save();
                if ($dominion->pack_id !== null && $dominion->pack->realm_id !== $realm->id) {
                    $dominion->pack->realm_id = $realm->id;
                    $dominion->pack->save();
                }
            }
            $realm->rating = $placeholderRealm->rating;
            $realm->save();
        }

        // Unlock realm pages
        $graveyard = $round->graveyard();
        if ($graveyard !== null && $graveyard->dominions()->count() == 0) {
            $round->update(['assignment_complete' => true]);
        }
    }

    /**
     * Send realm assignment notifications to all dominions in the round
     *
     * Notifies all players about their realm assignments through both in-game
     * notifications and email alerts. Includes realm number and Discord integration
     * status in the notification data to help players connect with their new
     * realm members.
     *
     * @param Round $round The game round to send notifications for
     * @return void
     */
    public function sendNotifications(Round $round): void
    {
        $notificationService = app(NotificationService::class);

        foreach ($round->realms()->get() as $realm) {
            foreach ($realm->dominions()->get() as $dominion) {
                $notificationService->queueNotification('realm_assignment', [
                    '_routeParams' => [$realm->number],
                    'realmNumber' => $realm->number,
                    'discordEnabled' => ($round->discord_guild_id !== null && $round->discord_guild_id !== '' && $realm->getSetting('usediscord') !== false)
                ]);
                $notificationService->sendNotifications($dominion, 'irregular_dominion');
            }
        }
    }

    /**
     * Get comprehensive assignment statistics
     *
     * Compiles detailed statistics about the completed realm assignment including
     * overall totals, per-realm breakdowns, playstyle distributions, and balance
     * metrics. Provides variance calculations and deviation measurements to assess
     * algorithm performance and assignment quality.
     *
     * @return array Comprehensive statistics array with overall metrics and per-realm details
     */
    public function getAssignmentStats(): array
    {
        $styles = array_keys(PlaceholderRealm::IDEAL_COMPOSITION);
        $shortNames = array_combine($styles, array_map(
            fn ($style) => str_replace('Affinity', '', $style),
            $styles
        ));

        $stats = [
            'realm_count' => $this->realms->count(),
            'total_players' => 0,
            'total_new_players' => 0,
            'total_experienced_players' => 0,
            'total_known_affinity_players' => 0,
            'average_realm_size' => 0,
            'average_realm_rating' => 0,
            'target_realm_strength' => $this->targetRealmStrength,
            'target_realm_size' => $this->targetRealmSize,
            'specialist_threshold' => PlaceholderRealm::SPECIALIST_THRESHOLD,
            'ideal_specialist_distribution' => array_combine(
                $shortNames,
                array_values(PlaceholderRealm::IDEAL_COMPOSITION)
            ),
            'overall_specialist_distribution' => array_fill_keys(array_values($shortNames), 0),
            'overall_playstyle_distribution' => array_fill_keys(array_values($shortNames), 0),
            'balance_metrics' => [
                'size_variance' => 0,
                'rating_variance' => 0,
                'max_size_deviation' => 0,
                'max_rating_deviation' => 0,
                'max_playstyle_deviation' => 0,
            ],
            'realms' => []
        ];

        $totalPlayers = 0;
        $totalNewPlayers = 0;
        $totalExperiencedPlayers = 0;
        $totalKnownAffinityPlayers = 0;
        $realmSizes = [];
        $realmRatings = [];
        $playstyleDeviations = [];
        $totalPlaystyleAffinities = array_fill_keys($styles, 0);
        $totalSpecialists = array_fill_keys($styles, 0);

        foreach ($this->realms as $realm) {
            $realmSize = $realm->size;
            $realmRating = $realm->rating;
            $playstyleDist = $realm->getPlaystyleComposition();
            $specialistCounts = $realm->getSpecialistCounts($realm->players);
            $playstyleDeviation = $realm->getPlaystyleDeviation();
            $knownAffinityPlayerCount = $realm->players->where('hasKnownAffinities', true)->count();
            $newPlayerCount = $realm->players->where('rating', '<=', self::NEW_PLAYER_RATING)->count();
            $experiencedPlayerCount = $realm->players->where('rating', '>', self::NEW_PLAYER_RATING)->count();

            // Accumulate totals
            $totalPlayers += $realmSize;
            $totalNewPlayers += $newPlayerCount;
            $totalExperiencedPlayers += $experiencedPlayerCount;
            $totalKnownAffinityPlayers += $knownAffinityPlayerCount;
            $realmSizes[] = $realmSize;
            $realmRatings[] = $realmRating;
            $playstyleDeviations[] = $playstyleDeviation;

            // Averages are per known-affinity player, so weight them back up by that
            // count rather than realm size before pooling them across realms.
            foreach ($styles as $style) {
                $totalPlaystyleAffinities[$style] += $playstyleDist[$style] * $knownAffinityPlayerCount;
                $totalSpecialists[$style] += $specialistCounts[$style];
            }

            $stats['realms'][] = [
                'id' => $realm->id,
                'size' => $realmSize,
                'total_rating' => round($realm->players->sum('rating'), 2),
                'average_rating' => $realmRating,
                'new_players' => $newPlayerCount,
                'experienced_players' => $experiencedPlayerCount,
                'known_affinity_players' => $knownAffinityPlayerCount,
                'packed_players' => $realm->packedPlayerCount(),
                'solo_players' => $realm->soloPlayers()->count(),
                'playstyle_distribution' => $playstyleDist,
                'specialist_counts' => array_combine($shortNames, array_values($specialistCounts)),
                'specialist_targets' => array_combine($shortNames, array_map(
                    fn ($ideal) => round($ideal / 100 * $knownAffinityPlayerCount, 2),
                    array_values(PlaceholderRealm::IDEAL_COMPOSITION)
                )),
                'playstyle_deviation' => round($playstyleDeviation, 2),
                'deviation_from_target_size' => round(abs($realmSize - $this->targetRealmSize), 2),
                'deviation_from_target_rating' => round(abs($realmRating - $this->targetRealmStrength), 2),
            ];
        }

        // Calculate overall statistics
        $stats['total_players'] = $totalPlayers;
        $stats['total_new_players'] = $totalNewPlayers;
        $stats['total_experienced_players'] = $totalExperiencedPlayers;
        $stats['total_known_affinity_players'] = $totalKnownAffinityPlayers;
        $stats['average_realm_size'] = $totalPlayers > 0 ? round($totalPlayers / $this->realms->count(), 2) : 0;
        $stats['average_realm_rating'] = count($realmRatings) > 0 ? round(array_sum($realmRatings) / count($realmRatings), 2) : 0;

        // Both distributions are per known-affinity player. The specialist shares are
        // directly comparable to ideal_specialist_distribution and are what assignment
        // actually optimises; the averages are reported for context only.
        if ($totalKnownAffinityPlayers > 0) {
            foreach ($styles as $style) {
                $stats['overall_playstyle_distribution'][$shortNames[$style]] =
                    round($totalPlaystyleAffinities[$style] / $totalKnownAffinityPlayers, 2);
                $stats['overall_specialist_distribution'][$shortNames[$style]] =
                    round(100 * $totalSpecialists[$style] / $totalKnownAffinityPlayers, 2);
            }
        }

        // Calculate balance metrics
        if (count($realmSizes) > 1) {
            $meanSize = array_sum($realmSizes) / count($realmSizes);
            $meanRating = array_sum($realmRatings) / count($realmRatings);

            $sizeVariances = array_map(fn ($size) => pow($size - $meanSize, 2), $realmSizes);
            $ratingVariances = array_map(fn ($rating) => pow($rating - $meanRating, 2), $realmRatings);

            $stats['balance_metrics'] = [
                'size_variance' => round(array_sum($sizeVariances) / count($sizeVariances), 2),
                'rating_variance' => round(array_sum($ratingVariances) / count($ratingVariances), 2),
                'max_size_deviation' => round(max(array_map(fn ($size) => abs($size - $this->targetRealmSize), $realmSizes)), 2),
                'max_rating_deviation' => round(max(array_map(fn ($rating) => abs($rating - $this->targetRealmStrength), $realmRatings)), 2),
                'max_playstyle_deviation' => round(max($playstyleDeviations), 2),
            ];
        }

        return $stats;
    }

    /**
     * Finds and returns the best realm for a new Dominion to settle in.
     *
     * @param Round $round
     * @param Race $race
     * @param User $user
     * @return Realm|null
     */
    public function findRealm(Round $round, Race $race, User $user, bool $useDiscord = true): ?Realm
    {
        // Pre-assignment period: use realm 0
        if (now() < $round->start_date->copy()->subHours(static::ASSIGNMENT_HOURS_BEFORE_START)) {
            return $round->realms()->where('number', 0)->first();
        }

        if (!$useDiscord && !($user->getEffectiveRating() > 1800)) {
            // Filter down to non-Discord realms (those with usediscord = false in settings)
            $nonDiscordRealms = $round->realms->filter(function ($realm) {
                return $realm->getSetting('usediscord') === false;
            });
            if ($nonDiscordRealms->isNotEmpty()) {
                return $nonDiscordRealms->random();
            }
        }

        // Get candidate realms with basic filtering
        $candidateRealms = $this->getCandidateRealms($round, $race);

        if ($candidateRealms->isEmpty()) {
            return null;
        }

        // Load detailed data and score candidates
        $player = $this->createPlayerForUser($user, $candidateRealms);

        // Return the best match
        return $this->selectBestRealm($candidateRealms, $player, $round);
    }

    /**
     * Get candidate realms with rating data included, excluding non-Discord realms.
     *
     * Realms containing a draft pack (a pack with more than MAX_PACKED_PLAYERS_PER_REALM
     * members, e.g. from a player-run external draft) are excluded from candidates unless
     * every other realm has at least 2 more total players — in which case the draft-pack
     * realm is the least-loaded option and should be considered.
     */
    /**
     * Constrain a dominion query to members still occupying a realm slot
     *
     * Locked and abandoned dominions are not coming back, so they must not count
     * toward a realm's size, rating, playstyle composition, or favorability. Every
     * membership query in the findRealm path routes through here so the definition
     * cannot drift between them — filtering the size count but not the scoring would
     * let a realm rank as small and still be scored as though it were full.
     *
     * Abandonment is scheduled 24h ahead, so a future abandoned_at is still active.
     * Matches Round::activeDominions(), RaidService and TickService.
     */
    private function scopeOccupiedSlots(Builder|Relation $query): Builder|Relation
    {
        return $query->whereNull('locked_at')
            ->where(function (Builder $query) {
                $query->whereNull('abandoned_at')
                    ->orWhere('abandoned_at', '>', now());
            });
    }

    public function getCandidateRealms(Round $round, Race $race): Collection
    {
        // Find realm IDs that contain a draft pack
        $draftPackRealmIds = Pack::where('round_id', $round->id)
            ->where('size', '>', self::MAX_PACKED_PLAYERS_PER_REALM)
            ->pluck('realm_id')
            ->filter()
            ->values();

        // Members are loaded by createPlaceholderRealm() instead of eager loaded here.
        // A partial select of the users table would leave affinities missing and
        // silently downgrade every member to unknown for playstyle scoring.
        //
        // The count drives both the smallest-realm ordering and the draft-pack
        // comparison, so it has to mean "players still occupying a slot". Locked and
        // abandoned dominions are not, and counting them makes a realm that needs
        // players look full. Matches Round::activeDominions() and RaidService.
        $query = Realm::active()
            ->where('number', '!=', 0)
            ->where('round_id', $round->id)
            ->withCount(['dominions as active_dominions_count' => function (Builder $query) {
                $this->scopeOccupiedSlots($query);
            }]);

        // Apply alignment filtering if needed
        if (!$round->mixed_alignment) {
            $query->where('alignment', $race->alignment);
        }

        // Get all potential realms first
        $realms = $query
            ->orderBy('active_dominions_count')
            ->get();

        // Filter out non-Discord realms (those with usediscord = false in settings)
        $discordRealms = $realms->filter(function ($realm) {
            return $realm->getSetting('usediscord') !== false;
        });

        // Exclude draft-pack realms unless every normal realm has at least 2 more players,
        // in which case the draft-pack realm is the least-loaded option and should be included
        if ($draftPackRealmIds->isNotEmpty()) {
            $normalRealms = $discordRealms->whereNotIn('id', $draftPackRealmIds);
            $draftPackRealms = $discordRealms->whereIn('id', $draftPackRealmIds);

            $allNormalRealmsAreLarger = $normalRealms->isNotEmpty() && $draftPackRealms->every(
                function ($draftRealm) use ($normalRealms) {
                    return $normalRealms->every(function ($r) use ($draftRealm) {
                        return $r->active_dominions_count >= $draftRealm->active_dominions_count + 2;
                    });
                }
            );

            if (!$allNormalRealmsAreLarger) {
                $discordRealms = $normalRealms;
            }
        }

        // Return top 3 smallest Discord-enabled candidates
        return $discordRealms->take(3);
    }

    /**
     * Create a Player object for the user with favorability data
     * Only loads data relevant to the candidate realms
     */
    public function createPlayerForUser(User $user, Collection $candidateRealms): Player
    {
        // Get all dominion IDs in candidate realms
        $candidateRealmIds = $candidateRealms->pluck('id');
        $targetUserIds = $this->scopeOccupiedSlots(Dominion::whereIn('realm_id', $candidateRealmIds))
            ->pluck('user_id')
            ->toArray();

        // Load favorability data only for relevant users
        $userFeedback = UserFeedback::where('source_id', $user->id)
            ->whereIn('target_id', $targetUserIds)
            ->get();

        $favorabilityMatrix = $userFeedback->groupBy('target_id')->mapWithKeys(function ($feedbacks, $targetId) {
            $positive = $feedbacks->where('endorsed', true)->count();
            $negative = $feedbacks->where('endorsed', false)->count();
            return [$targetId => $positive - $negative];
        })->toArray();

        return Player::fromUser($user, [
            'id' => $user->id,
            'userId' => $user->id,
            'packId' => null, // Individual registration
            'favorability' => $favorabilityMatrix,
        ]);
    }

    /**
     * Load what existing players think of the incoming player
     *
     * getCompatibilityScore() sums favorability in both directions. Scoring with only
     * the incoming player's own outbound feedback silently discards every downvote
     * cast about them by the people already in the realm, which is the half that
     * should keep them out.
     *
     * @param Player $player The incoming player
     * @param Collection $candidateRealms Realms whose members may have rated them
     * @return array Member user id => [incoming user id => score]
     */
    public function getInboundFavorability(Player $player, Collection $candidateRealms): array
    {
        $sourceUserIds = $this->scopeOccupiedSlots(Dominion::whereIn('realm_id', $candidateRealms->pluck('id')))
            ->pluck('user_id');

        return UserFeedback::where('target_id', $player->userId)
            ->whereIn('source_id', $sourceUserIds)
            ->get()
            ->groupBy('source_id')
            ->mapWithKeys(function ($feedbacks, $sourceId) use ($player) {
                $positive = $feedbacks->where('endorsed', true)->count();
                $negative = $feedbacks->where('endorsed', false)->count();
                return [$sourceId => [$player->userId => $positive - $negative]];
            })
            ->toArray();
    }

    /**
     * Select the best realm using compatibility and rating balance scoring
     */
    public function selectBestRealm(Collection $candidateRealms, Player $player, Round $round): ?Realm
    {
        // Calculate dynamic targets from all realms in the round
        $this->calculateDynamicTargets($round);

        $inboundFavorability = $this->getInboundFavorability($player, $candidateRealms);

        $bestRealm = null;
        $bestScore = -INF;

        foreach ($candidateRealms as $realm) {
            // Create placeholder realm for scoring
            $placeholderRealm = $this->createPlaceholderRealm($realm, $inboundFavorability);

            // Calculate compatibility score using existing method
            $compatibilityScore = $placeholderRealm->getCompatibilityScore(collect([$player]));

            // Calculate rating balance score (now uses dynamic targets)
            $balanceScore = $this->calculateBalanceScore($placeholderRealm, collect([$player]));

            // Combine scores (weight balance more heavily for individual assignments)
            $totalScore = $compatibilityScore + ($balanceScore * 2);

            if ($totalScore > $bestScore) {
                $bestScore = $totalScore;
                $bestRealm = $realm;
            }
        }

        return $bestRealm;
    }

    /**
     * Convert a database Realm to PlaceholderRealm for scoring
     *
     * Loads the full user record for each member rather than reusing any partially
     * selected relation, so affinities are present and the playstyle term scores the
     * realm on real data instead of getAffinity()'s fallbacks.
     *
     * @param Realm $realm The realm to convert
     * @param array $favorabilityByUserId Member user id => favorability map for that member
     */
    public function createPlaceholderRealm(Realm $realm, array $favorabilityByUserId = []): PlaceholderRealm
    {
        $members = $this->scopeOccupiedSlots($realm->dominions()->human())->with('user')->get();

        $players = $members->map(function ($dominion) use ($favorabilityByUserId) {
            return Player::fromUser($dominion->user, [
                'id' => $dominion->user_id,
                'userId' => $dominion->user_id,
                'packId' => $dominion->pack_id,
                'favorability' => $favorabilityByUserId[$dominion->user_id] ?? [],
            ]);
        });

        return new PlaceholderRealm($realm->id, $players);
    }

    /**
     * Calculate dynamic targets across every assigned realm in the round.
     *
     * Reads the persisted average rating from the realms table — the value is
     * kept fresh during the registration window by DominionFactory::create and
     * frozen once the round starts, so this gives the global rating midpoint
     * each new registration should balance toward. Excludes the graveyard
     * realm so its zero rating and count don't drag the targets down.
     */
    public function calculateDynamicTargets(Round $round): void
    {
        $realms = Realm::where('round_id', $round->id)
            ->where('number', '!=', 0)
            ->withCount(['dominions' => function (Builder $query) {
                $this->scopeOccupiedSlots($query);
            }])
            ->get(['id', 'rating']);

        $realmCount = $realms->count();

        if ($realmCount === 0) {
            $this->targetRealmStrength = 1500;
            $this->targetRealmSize = 12;
            return;
        }

        $this->targetRealmStrength = $realms->avg('rating');
        $this->targetRealmSize = floor($realms->sum('dominions_count') / $realmCount);
    }
}
