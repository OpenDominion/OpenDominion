<?php

namespace OpenDominion\Services;

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
    public string $id;
    public float $rating;
    public ?string $packId;
    public bool $hasDiscord = true;
    public array $favorability = []; // player_id => score

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
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get favorability score with another player
     *
     * Returns the favorability rating this player has given to another player.
     * Positive values indicate endorsement, negative values indicate negative feedback.
     * Returns 0 if no feedback has been given.
     *
     * @param string $playerId The ID of the other player
     * @return float Favorability score (-1 to 1, typically)
     */
    public function getFavorabilityWith(string $playerId): float
    {
        return $this->favorability[$playerId] ?? 0;
    }

    /**
     * Get player's primary playstyle based on highest affinity
     *
     * Compares all playstyle affinities (attacker, converter, explorer, ops) and
     * returns the playstyle with the highest value. Used for playstyle distribution
     * analysis and realm balancing.
     *
     * @return string Primary playstyle ('attacker', 'converter', 'explorer', or 'ops')
     */
    public function getPrimaryPlaystyle(): string
    {
        $affinities = [
            'attacker' => $this->attackerAffinity,
            'converter' => $this->converterAffinity,
            'explorer' => $this->explorerAffinity,
            'ops' => $this->opsAffinity,
        ];

        return array_keys($affinities, max($affinities))[0];
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
     * is calculated as the sum of all member ratings.
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
        $this->rating = $members->sum('rating');
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
                $totalScore += $member1->getFavorabilityWith($member2->id);
                $totalScore += $member2->getFavorabilityWith($member1->id);
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
     * @var array Ideal average playstyle affinities per player for balanced realms
     */
    public const IDEAL_COMPOSITION = [
        'attackerAffinity' => 50,
        'converterAffinity' => 30,
        'explorerAffinity' => 50,
        'opsAffinity' => 30,
    ];

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
     * Recalculates the realm's size (player count) and total rating
     * (sum of all player ratings). Called whenever players are added
     * or removed from the realm.
     */
    public function update(): void
    {
        $this->size = $this->players->count();
        $this->rating = $this->players->sum('rating');
    }

    /**
     * Calculate compatibility score for adding players to this realm
     *
     * Computes a comprehensive compatibility score that includes both
     * favorability ratings between players and playstyle fit. The score
     * considers both existing realm members and the potential new players.
     *
     * Heavy penalties (-100) are applied when favorability is very negative
     * to discourage placing conflicting players together.
     *
     * @param Collection $players Collection of Player objects to evaluate
     * @return float Total compatibility score (higher is better)
     */
    public function getCompatibilityScore(Collection $players): float
    {
        $favorabilityScore = 0;
        $totalScore = 0;

        foreach ($players as $newMember) {
            $favorabilityScore = 0;
            foreach ($this->players as $realmMember) {
                $favorabilityScore += $realmMember->getFavorabilityWith($newMember->id);
                $favorabilityScore += $newMember->getFavorabilityWith($realmMember->id);
            }
            if ($favorabilityScore < -10) {
                // Heavy penalty for conflicts
                $favorabilityScore = -100;
            }
            $totalScore += $favorabilityScore;
        }

        // TODO: Weight this
        $totalScore += $this->calculatePlaystyleScore($players);

        return $totalScore;
    }

    /**
     * Calculate playstyle score for adding players to this realm
     *
     * Evaluates how adding players would move the realm closer to or further from
     * the ideal playstyle composition. Uses configurable ideal ratios based on
     * game balance research.
     *
     * @param Collection $players Players to evaluate for addition
     * @return float Balance improvement score (positive is better)
     */
    public function calculatePlaystyleScore(Collection $players): float
    {
        // Get current and projected compositions
        $currentComp = $this->getPlaystyleComposition();
        $currentDeviation = $this->calculatePlaystyleDeviation($currentComp);

        // Simulate adding new players
        $newComp = $currentComp;
        foreach ($players as $player) {
            $newComp['attackerAffinity'] += $player->attackerAffinity;
            $newComp['converterAffinity'] += $player->converterAffinity;
            $newComp['explorerAffinity'] += $player->explorerAffinity;
            $newComp['opsAffinity'] += $player->opsAffinity;
        }

        $newDeviation = $this->calculatePlaystyleDeviation($newComp);

        // Return improvement (positive if we got closer to ideal)
        return $currentDeviation - $newDeviation;
    }

    /**
     * Get realm's current playstyle composition as averages
     *
     * Calculates the average playstyle affinity for each category across all players.
     * Returns averages that can be directly compared to IDEAL_COMPOSITION values.
     *
     * @return array Associative array with playstyle averages (attackerAffinity, converterAffinity, etc.)
     */
    public function getPlaystyleComposition(): array
    {
        if ($this->players->count() == 0) {
            return [
                'attackerAffinity' => 0,
                'converterAffinity' => 0,
                'explorerAffinity' => 0,
                'opsAffinity' => 0,
            ];
        }

        return [
            'attackerAffinity' => $this->players->avg('attackerAffinity'),
            'converterAffinity' => $this->players->avg('converterAffinity'),
            'explorerAffinity' => $this->players->avg('explorerAffinity'),
            'opsAffinity' => $this->players->avg('opsAffinity'),
        ];
    }

    /**
     * Check if player has hard conflicts with realm members
     *
     * Calculates the total favorability score between the player and all
     * existing realm members. A hard conflict exists when the total favorability
     * is less than -10, indicating significant negative relationships.
     *
     * @param Player $player The player to check for conflicts
     * @return bool True if hard conflicts exist (favorability < -10)
     */
    public function hasHardConflicts(Player $player): bool
    {
        $favorabilityScore = 0;

        foreach ($this->players as $member) {
            $favorabilityScore += $player->getFavorabilityWith($member->id);
            $favorabilityScore += $member->getFavorabilityWith($player->id);
        }
        if ($favorabilityScore < -10) {
            return true;
        }
        return false;
    }

    /**
     * Calculate how far a composition deviates from ideal averages
     *
     * Measures the total absolute deviation from the ideal playstyle composition.
     * Both composition and ideal values are averages, so direct comparison works.
     * Lower values indicate better balance according to the configured ideal averages.
     *
     * @param array $composition Current playstyle averages
     * @return float Total deviation from ideal (lower is better)
     */
    public function calculatePlaystyleDeviation(array $composition): float
    {
        $idealAverages = static::IDEAL_COMPOSITION;
        $totalDeviation = 0;

        foreach ($idealAverages as $style => $idealAverage) {
            $actualAverage = $composition[$style] ?? 0;
            $totalDeviation += abs($actualAverage - $idealAverage);
        }

        return $totalDeviation;
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

    /**
     * Constructor - initialize collections
     */
    public function __construct()
    {
        $this->players = collect();
        $this->packs = collect();
        $this->realms = collect();
        // Target values will be calculated dynamically when needed
        $this->targetRealmSize = 12;
        $this->targetRealmStrength = 1500;
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
        $playerCount = $this->players->count();
        $this->targetRealmStrength = $this->players->avg('rating');

        $this->loadPacks();
        $realmCount = $this->calculateRealmCount();
        $this->targetRealmSize = $playerCount / $realmCount;

        // Assign large packs
        $this->createPlaceholderRealms();

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
     * to Player objects with favorability matrices, playstyle ratings, and
     * other assignment-relevant data. Placeholder playstyle data is used
     * until proper calculation is implemented.
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
            // Perform pre-processing
            $playerFeedback = $userFeedback->where('source_id', $dominion->user_id);
            $favorabilityMatrix = $playerFeedback->mapWithKeys(function ($feedback) {
                return [$feedback->target_id => $feedback->endorsed ? 1 : -1];
            })->toArray();
            // Determine Discord preference - false only if setting exists and is explicitly false
            $discordSetting = $dominion->getSetting('usediscord');
            $hasDiscord = !($discordSetting !== null && $discordSetting === false);

            // Create player
            $player = new Player([
                'id' => $dominion->id,
                'packId' => $dominion->pack_id,
                'rating' => $dominion->user->rating ?? 0,
                'favorability' => $favorabilityMatrix,
                'hasDiscord' => $hasDiscord,
                'attackerAffinity' => $dominion->user->getAffinity('attacker'),
                'converterAffinity' => $dominion->user->getAffinity('converter'),
                'explorerAffinity' => $dominion->user->getAffinity('explorer'),
                'opsAffinity' => $dominion->user->getAffinity('ops'),
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
            $this->players->forget($packMembers->pluck('id'));
        }
    }

    /**
     * Calculate optimal number of realms based on pack sizes
     *
     * The number of realms is primarily determined by the number of large packs
     * (>3 players), with adjustments to stay within min/max bounds. Large packs
     * may be downgraded or small packs upgraded to achieve the target count.
     *
     * @return int Number of realms to create
     */
    public function calculateRealmCount(): int
    {
        $largePacks = $this->packs->where('large', true)->count();

        if ($largePacks > self::ASSIGNMENT_MAX_REALM_COUNT) {
            $this->downgradePacks(self::ASSIGNMENT_MAX_REALM_COUNT - $largePacks);
            return self::ASSIGNMENT_MAX_REALM_COUNT;
        } elseif ($largePacks < self::ASSIGNMENT_MIN_REALM_COUNT) {
            $this->upgradePacks(self::ASSIGNMENT_MIN_REALM_COUNT - $largePacks);
            return self::ASSIGNMENT_MIN_REALM_COUNT;
        } else {
            return $largePacks;
        }
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
     * Create initial realms, prioritizing non-Discord realms first
     *
     * Creates non-Discord realms first for solo players who opted out of Discord,
     * then creates regular Discord-enabled realms from packs.
     */
    public function createPlaceholderRealms(): void
    {
        // Step 1: Create non-Discord realms first for solo players only
        $this->createNonDiscordRealms();

        // Step 2: Create regular Discord-enabled realms from remaining packs
        $largePacks = $this->packs->where('large', true);
        foreach ($largePacks as $idx => $pack) {
            $realm = new PlaceholderRealm($idx, $pack->members);
            $this->realms->push($realm);
            $this->packs->forget($pack->id);
        }

        // Step 3: Use small packs if we need more realms
        if ($this->realms->count() < self::ASSIGNMENT_MIN_REALM_COUNT) {
            $smallPacks = $this->packs->where('large', false);
            foreach ($smallPacks as $idx => $pack) {
                $realm = new PlaceholderRealm($idx, $pack->members);
                $this->realms->push($realm);
                $this->packs->forget($pack->id);
                if ($this->realms->count() >= self::ASSIGNMENT_MIN_REALM_COUNT) {
                    break;
                }
            }
        }

        // Step 4: Create empty Discord-enabled realms if needed
        if ($this->realms->count() < self::ASSIGNMENT_MIN_REALM_COUNT) {
            foreach (range($this->realms->count(), self::ASSIGNMENT_MIN_REALM_COUNT - 1) as $idx) {
                $realm = new PlaceholderRealm($idx, collect());
                $this->realms->push($realm);
            }
        }
    }

    /**
     * Create non-Discord realms for solo players who opted out of Discord
     *
     * Creates a single non-Discord realm unless there are more than 12
     * non-Discord solo players, in which case additional realms are created.
     */
    private function createNonDiscordRealms(): void
    {
        $nonDiscordSoloPlayers = $this->players->where('hasDiscord', false);
        $totalNonDiscordPlayers = $nonDiscordSoloPlayers->count();

        if ($totalNonDiscordPlayers === 0) {
            return; // No non-Discord players, nothing to do
        }

        // Determine number of non-Discord realms needed (prefer 1, but max 14 players per realm)
        $nonDiscordRealmCount = max(1, ceil($totalNonDiscordPlayers / 14));

        // Create non-Discord realms
        for ($i = 0; $i < $nonDiscordRealmCount; $i++) {
            $realm = new PlaceholderRealm("non-discord-{$i}", collect(), false);
            $this->realms->push($realm);
        }

        // Assign non-Discord solo players to non-Discord realms (round-robin)
        $soloIndex = 0;
        foreach ($nonDiscordSoloPlayers as $player) {
            $realmIndex = $this->realms->count() - $nonDiscordRealmCount + ($soloIndex % $nonDiscordRealmCount);
            $this->realms[$realmIndex]->addPlayer($player);
            $this->players->forget($player->id);
            $soloIndex++;
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
        $compatibility = $realm->getCompatibilityScore($pack->members);
        $balanceScore = $this->calculateBalanceScore($realm, $pack->members);
        $opportunityCost = $this->calculateOpportunityCost($realm, $pack, $compatibility + $balanceScore);

        // TODO: Weight this
        return $compatibility + $balanceScore + $opportunityCost;
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

        // Instead of just rewarding improvement, penalize final distance from target
        // This encourages assignments that result in realms closer to the target
        $baseScore = 200 - $newDeviation; // Higher score for realms closer to target

        // Bonus for improvement (but secondary to final position)
        $improvementBonus = ($currentDeviation - $newDeviation) * 0.5;

        // Strong exponential penalty for very unbalanced final states
        $unbalancePenalty = 0;
        if ($newDeviation > 150) {
            $unbalancePenalty = pow($newDeviation / 100, 2) * 50;
        }

        return $baseScore + $improvementBonus - $unbalancePenalty;
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
     * Provides a large penalty (-1000) for realms at or above target size to
     * enforce equal distribution. Realms below target receive a small bonus
     * proportional to how many players they need. This ensures all realms
     * reach approximately the same size.
     *
     * @param PlaceholderRealm $realm The realm to evaluate
     * @return float Size bonus/penalty (-1000 for full realms, positive for others)
     */
    public function calculateSizeBonus(PlaceholderRealm $realm): float
    {
        $currentSize = $realm->players->count();
        $targetSize = (int) round($this->targetRealmSize);

        // Large penalty for realms at or above target size
        if ($currentSize >= $targetSize) {
            return -1000; // Effectively eliminates this realm from consideration
        }

        // Small bonus for realms that need players
        return ($targetSize - $currentSize) * 10;
    }

    /**
     * Assign solo players to realms
     *
     * Orchestrates the assignment of individual players in two phases:
     * Phase 1 distributes new players (rating=0) evenly using round-robin.
     * Phase 2 assigns experienced players using full scoring with size constraints.
     * This ensures fair distribution while optimizing for compatibility and balance.
     */
    public function assignSolos(): void
    {
        // Phase 1: Distribute new players using round-robin
        $newPlayers = $this->players->where('rating', 0)->values(); // Get indexed collection
        $realmCount = $this->realms->count();

        // Assign all new players using round-robin across realms
        foreach ($newPlayers as $index => $newPlayer) {
            $realmIndex = $index % $realmCount;
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
     * Assigns players with rating > 0 using comprehensive scoring that considers
     * compatibility, balance, and size constraints. Players are sorted by rating
     * (highest first) for strategic placement. Hard conflicts are avoided and
     * the size penalty ensures equal distribution across realms.
     */
    public function assignExperiencedPlayers(): void
    {
        // Sort by rating (highest first) for strategic placement, with shuffle for tied ratings
        $sortedPlayers = $this->players->sortByDesc('rating')->shuffle()->values();

        foreach ($sortedPlayers as $player) {
            $bestRealm = null;
            $bestScore = -INF;

            foreach ($this->realms as $realm) {
                // Skip realms where player would have hard conflicts
                if ($realm->hasHardConflicts($player)) {
                    continue;
                }

                // Calculate compatibility and balance scores
                $compatibilityScore = $realm->getCompatibilityScore(collect([$player]));
                $balanceScore = $this->calculateBalanceScore($realm, collect([$player]));
                $sizeBonus = $this->calculateSizeBonus($realm);

                // Weight the balance score more heavily to prevent extreme rating imbalances
                $weightedBalanceScore = $balanceScore * 5; // Increase balance importance

                $totalScore = $compatibilityScore + $weightedBalanceScore + $sizeBonus;

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
     * Post-assignment optimization through randomized player swapping
     *
     * Performs iterative optimization by randomly sampling pairs of solo players
     * from different realms and swapping them if beneficial. This approach is more
     * efficient than exhaustive search and better explores the solution space.
     * Runs for up to 15 iterations or until no more improvements are found.
     */
    public function optimizeAssignments(): void
    {
        $improved = true;
        $iterations = 0;
        $maxIterations = 15;
        $totalSwaps = 0;
        $samplesPerIteration = 200; // Number of random pairs to test per iteration

        while ($improved && $iterations < $maxIterations) {
            $improved = false;
            $iterations++;

            // Collect all solo players with their realm assignments
            $soloPlayers = collect();
            foreach ($this->realms as $realm) {
                foreach ($realm->soloPlayers() as $player) {
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
        // 1. Check for hard conflicts first (early exit)
        if ($realm2->hasHardConflicts($solo1) || $realm1->hasHardConflicts($solo2)) {
            return false;
        }

        // 2. Calculate accurate swap scores by removing players first, then evaluating placements

        // Remove players temporarily to get accurate base scores
        $realm1->players->forget($solo1->id);
        $realm2->players->forget($solo2->id);
        $realm1->update();
        $realm2->update();

        // Calculate base scores without either player
        $currentScore1 = $this->calculateBalanceScore($realm1, collect([$solo1]));
        $currentScore2 = $this->calculateBalanceScore($realm2, collect([$solo2]));
        $currentTotal = $currentScore1 + $currentScore2;

        // Calculate post-swap scores
        $newScore1 = $this->calculateBalanceScore($realm1, collect([$solo2]));
        $newScore2 = $this->calculateBalanceScore($realm2, collect([$solo1]));
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
        $stats = [
            'realm_count' => $this->realms->count(),
            'total_players' => 0,
            'total_new_players' => 0,
            'total_experienced_players' => 0,
            'average_realm_size' => 0,
            'average_realm_rating' => 0,
            'target_realm_strength' => $this->targetRealmStrength,
            'target_realm_size' => $this->targetRealmSize,
            'overall_playstyle_distribution' => [
                'attacker' => 0,
                'converter' => 0,
                'explorer' => 0,
                'ops' => 0,
            ],
            'balance_metrics' => [
                'size_variance' => 0,
                'rating_variance' => 0,
                'max_size_deviation' => 0,
                'max_rating_deviation' => 0,
            ],
            'realms' => []
        ];

        $totalPlayers = 0;
        $totalNewPlayers = 0;
        $totalExperiencedPlayers = 0;
        $totalRating = 0;
        $realmSizes = [];
        $realmRatings = [];

        foreach ($this->realms as $realm) {
            $realmSize = $realm->size;
            $realmRating = $realm->players->avg('rating');
            $playstyleDist = $realm->getPlaystyleComposition();
            $newPlayerCount = $realm->players->where('rating', 0)->count();
            $experiencedPlayerCount = $realm->players->where('rating', '>', 0)->count();

            // Accumulate totals
            $totalPlayers += $realmSize;
            $totalNewPlayers += $newPlayerCount;
            $totalExperiencedPlayers += $experiencedPlayerCount;
            $totalRating += $realm->rating;
            $realmSizes[] = $realmSize;
            $realmRatings[] = $realmRating;

            $stats['realms'][] = [
                'id' => $realm->id,
                'size' => $realmSize,
                'total_rating' => round($realm->rating, 2),
                'average_rating' => $realmRating,
                'new_players' => $newPlayerCount,
                'experienced_players' => $experiencedPlayerCount,
                'packed_players' => $realm->packedPlayerCount(),
                'solo_players' => $realm->soloPlayers()->count(),
                'playstyle_distribution' => $playstyleDist,
                'deviation_from_target_size' => round(abs($realmSize - $this->targetRealmSize), 2),
                'deviation_from_target_rating' => round(abs($realmRating - $this->targetRealmStrength), 2),
            ];
        }

        // Calculate overall statistics
        $stats['total_players'] = $totalPlayers;
        $stats['total_new_players'] = $totalNewPlayers;
        $stats['total_experienced_players'] = $totalExperiencedPlayers;
        $stats['average_realm_size'] = $totalPlayers > 0 ? round($totalPlayers / $this->realms->count(), 2) : 0;
        $stats['average_realm_rating'] = $totalPlayers > 0 ? round($totalRating / $totalPlayers, 2) : 0;

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
    public function findRealm(Round $round, Race $race, User $user): ?Realm
    {
        // Pre-assignment period: use realm 0
        if (now() < $round->start_date->copy()->subHours(static::ASSIGNMENT_HOURS_BEFORE_START)) {
            return $round->realms()->where('number', 0)->first();
        }

        // Get candidate realms with basic filtering
        $candidateRealms = $this->getCandidateRealms($round, $race);

        if ($candidateRealms->isEmpty()) {
            return null;
        }

        // Load detailed data and score candidates
        $player = $this->createPlayerForUser($user, $candidateRealms);

        // Return the best match
        return $this->selectBestRealm($candidateRealms, $player);
    }

    /**
     * Get candidate realms with rating data included, excluding non-Discord realms
     */
    public function getCandidateRealms(Round $round, Race $race): Collection
    {
        $query = Realm::active()
            ->where('number', '!=', 0)
            ->where('round_id', $round->id)
            ->withCount(['dominions as active_dominions_count' => function ($query) {
                $query->where('protection_finished', true);
            }])
            ->with(['dominions' => function ($query) {
                $query->select('realm_id', 'user_id')
                      ->with(['user' => function ($userQuery) {
                          $userQuery->select('id', 'rating');
                      }]);
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
        $targetUserIds = Dominion::whereIn('realm_id', $candidateRealmIds)
            ->pluck('user_id')
            ->toArray();

        // Load favorability data only for relevant users
        $userFeedback = UserFeedback::where('source_id', $user->id)
            ->whereIn('target_id', $targetUserIds)
            ->get();

        $favorabilityMatrix = $userFeedback->mapWithKeys(function ($feedback) {
            return [$feedback->target_id => $feedback->endorsed ? 1 : -1];
        })->toArray();

        return new Player([
            'id' => $user->id,
            'packId' => null, // Individual registration
            'rating' => $user->rating ?? 0,
            'favorability' => $favorabilityMatrix,
            'attackerAffinity' => $user->getAffinity('attacker'),
            'converterAffinity' => $user->getAffinity('converter'),
            'explorerAffinity' => $user->getAffinity('explorer'),
            'opsAffinity' => $user->getAffinity('ops'),
        ]);
    }

    /**
     * Select the best realm using compatibility and rating balance scoring
     */
    public function selectBestRealm(Collection $candidateRealms, Player $player): ?Realm
    {
        // Calculate dynamic targets from candidate realms
        $this->calculateDynamicTargets($candidateRealms);

        $bestRealm = null;
        $bestScore = -INF;

        foreach ($candidateRealms as $realm) {
            // Create placeholder realm for scoring
            $placeholderRealm = $this->createPlaceholderRealm($realm);

            // Check for hard conflicts first (early exit)
            if ($placeholderRealm->hasHardConflicts($player)) {
                continue;
            }

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
     */
    public function createPlaceholderRealm(Realm $realm): PlaceholderRealm
    {
        $players = $realm->dominions->map(function ($dominion) {
            return new Player([
                'id' => $dominion->user_id,
                'packId' => $dominion->pack_id,
                'rating' => $dominion->user->rating ?? 0,
                'favorability' => [], // Not needed for existing players in this context
                'attackerAffinity' => $dominion->user->getAffinity('attacker'),
                'converterAffinity' => $dominion->user->getAffinity('converter'),
                'explorerAffinity' => $dominion->user->getAffinity('explorer'),
                'opsAffinity' => $dominion->user->getAffinity('ops'),
            ]);
        });

        return new PlaceholderRealm($realm->id, $players);
    }

    /**
     * Calculate dynamic targets based on current realm states
     */
    public function calculateDynamicTargets(Collection $candidateRealms): void
    {
        $realmSizes = [];
        $totalRating = 0;
        $totalPlayers = 0;

        foreach ($candidateRealms as $realm) {
            $realmSize = $realm->dominions->count();
            $realmRating = $realm->dominions->sum(function ($dominion) {
                return $dominion->user->rating ?? 0;
            });

            $realmSizes[] = $realmSize;
            $totalRating += $realmRating;
            $totalPlayers += $realmSize;
        }

        // Set dynamic targets based on current state
        $this->targetRealmSize = count($realmSizes) > 0 ? array_sum($realmSizes) / count($realmSizes) : 12;
        $this->targetRealmStrength = $totalPlayers > 0 ? $totalRating / $totalPlayers : 1500;
    }
}
