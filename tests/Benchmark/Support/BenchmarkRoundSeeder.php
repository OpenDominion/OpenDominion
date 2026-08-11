<?php

namespace OpenDominion\Tests\Benchmark\Support;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Models\User;
use OpenDominion\Services\Dominion\TickService;
use RuntimeException;

/**
 * Builds a round with N dominions carrying enough state that every branch of the
 * tick does real work.
 *
 * Deliberate choices, and what they cost in fidelity:
 *
 *  - Dominions are created with DominionFactory::createNonPlayer inside
 *    Dominion::withoutEvents(). The DominionSaved listener runs a full networth
 *    calculation plus a precalculateTick on every save, which would make seeding
 *    N dominions cost more than the tick we are measuring.
 *  - Users are attached to a fraction of the dominions afterwards rather than
 *    created through the player factory. NotificationService::sendNotifications
 *    early-returns for user-less dominions, so a bot-only fixture would not
 *    measure the notification path at all. Production is roughly 20% players.
 *  - mt_srand fixes every structural property of the fixture, so query counts
 *    are reproducible. See withDeterministicRandomness() for what deliberately
 *    stays random and why.
 */
final class BenchmarkRoundSeeder
{
    public const REALM_SIZE = 12;

    public const RANDOM_SEED = 20260811;

    private const PLAYER_RATIO = 0.2;

    /**
     * Queue rows written for every dominion, on top of the training rows
     * createNonPlayer already writes.
     *
     * Hours are chosen so a single tick exercises both halves of the queue
     * handling: rows at hours = 1 decrement to 0 and drive cleanupQueues plus its
     * notifications, rows at hours = 2 decrement to 1 and are read by
     * precalculateTick as next hour's incoming resources.
     *
     * No training row for military_unit2 or military_unit3 may sit in hours 4-9:
     * createNonPlayer (DominionFactory.php:626) already queues those, and
     * dominion_queue is keyed on (dominion_id, source, resource, hours).
     *
     * @var array<int, array{0: string, 1: string, 2: int, 3: int}>
     */
    private const QUEUE_ROWS = [
        ['construction', 'building_home', 1, 25],
        ['construction', 'building_farm', 2, 15],
        ['construction', 'building_lumberyard', 9, 10],
        ['exploration', 'land_plain', 1, 12],
        ['exploration', 'land_forest', 2, 8],
        ['exploration', 'land_hill', 6, 9],
        ['training', 'military_unit1', 1, 40],
        ['training', 'military_unit2', 2, 60],
        ['training', 'military_unit3', 3, 20],
        ['training', 'military_draftees', 11, 5],
        ['invasion', 'military_unit1', 1, 30],
        ['invasion', 'military_unit2', 5, 45],
    ];

    /**
     * Spells createNonPlayer already casts on every dominion - picking either
     * again would collide on the (dominion_id, spell_id) primary key.
     *
     * @var array<int, string>
     */
    private const FACTORY_SEEDED_SPELL_KEYS = ['ares_call', 'midas_touch'];

    /**
     * Durations assigned to the beneficial and harmful spells picked below.
     *
     * A duration of 1 decrements to 0 and drives cleanupActiveSpells (and the
     * lazy ->spell N+1 in finding 2.2); a duration of 2 decrements to 1 and is
     * picked up by the expiring-spells select.
     *
     * @var array<int, int>
     */
    private const BENEFICIAL_DURATIONS = [1, 2, 6, 10];

    /** @var array<int, int> */
    private const HARMFUL_DURATIONS = [1, 8];

    public function __construct(
        private readonly DominionFactory $dominionFactory,
        private readonly RealmFactory $realmFactory,
        private readonly TickService $tickService,
    ) {
    }

    public function seed(int $dominionCount): BenchmarkRound
    {
        if ($dominionCount < 1) {
            throw new RuntimeException('Benchmark fixtures need at least one dominion.');
        }

        return $this->withDeterministicRandomness(function () use ($dominionCount): BenchmarkRound {
            $round = $this->createRound();
            $races = $this->racesByAlignment();

            $dominionIds = [];
            $realmCount = 0;

            while (count($dominionIds) < $dominionCount) {
                $alignment = ($realmCount % 2 === 0) ? 'good' : 'evil';
                $realm = $this->realmFactory->create($round, $alignment);
                $realmCount++;

                $remaining = $dominionCount - count($dominionIds);
                $inThisRealm = min(self::REALM_SIZE, $remaining);

                for ($i = 0; $i < $inThisRealm; $i++) {
                    $dominionIds[] = $this->createDominion($realm, $races[$alignment], count($dominionIds));
                }
            }

            $playerCount = $this->attachUsers($dominionIds);
            $this->seedQueues($dominionIds);
            $this->seedSpells($dominionIds);

            $this->precalculate($dominionIds);

            return new BenchmarkRound(
                $round->fresh(),
                $dominionIds,
                $realmCount,
                $playerCount,
                DB::table('dominion_queue')->whereIn('dominion_id', $dominionIds)->count(),
                DB::table('dominion_spells')->whereIn('dominion_id', $dominionIds)->count()
            );
        });
    }

    private function createRound(): Round
    {
        return Round::create([
            'round_league_id' => 1,
            'number' => 1,
            'name' => 'Benchmark Round',
            'start_date' => new Carbon('-7 days'),
            'end_date' => new Carbon('+40 days'),
            'pack_size' => 6,
            'mixed_alignment' => false,
        ]);
    }

    /**
     * @return array<string, Collection<int, Race>>
     */
    private function racesByAlignment(): array
    {
        $races = Race::with(['perks', 'units.perks'])
            ->where('playable', true)
            ->orderBy('id')
            ->get();

        $byAlignment = [
            'good' => $races->where('alignment', 'good')->values(),
            'evil' => $races->where('alignment', 'evil')->values(),
        ];

        foreach ($byAlignment as $alignment => $collection) {
            if ($collection->isEmpty()) {
                throw new RuntimeException("No playable {$alignment} races found - is the database seeded?");
            }
        }

        return $byAlignment;
    }

    /**
     * @param Collection<int, Race> $races
     */
    private function createDominion(Realm $realm, Collection $races, int $index): int
    {
        $race = $races[$index % $races->count()];
        $landSize = 450 + mt_rand(0, 150);

        $dominion = Dominion::withoutEvents(fn (): ?Dominion => $this->dominionFactory->createNonPlayer(
            $realm,
            $race,
            sprintf('Bench Ruler %d', $index),
            sprintf('Bench Dominion %d', $index),
            $landSize
        ));

        if ($dominion === null) {
            throw new RuntimeException("Failed to create benchmark dominion {$index}.");
        }

        return $dominion->id;
    }

    /**
     * Gives PLAYER_RATIO of the dominions a user so the notification path is
     * measured rather than skipped.
     *
     * @param array<int, int> $dominionIds
     */
    private function attachUsers(array $dominionIds): int
    {
        $playerCount = max(1, (int)round(count($dominionIds) * self::PLAYER_RATIO));

        for ($i = 0; $i < $playerCount; $i++) {
            $user = User::factory()->create();

            DB::table('dominions')
                ->where('id', $dominionIds[$i])
                ->update(['user_id' => $user->id]);
        }

        return $playerCount;
    }

    /**
     * @param array<int, int> $dominionIds
     */
    private function seedQueues(array $dominionIds): void
    {
        $now = now();
        $rows = [];

        foreach ($dominionIds as $dominionId) {
            foreach (self::QUEUE_ROWS as [$source, $resource, $hours, $amount]) {
                $rows[] = [
                    'dominion_id' => $dominionId,
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $hours,
                    'amount' => $amount,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('dominion_queue')->insert($chunk);
        }
    }

    /**
     * @param array<int, int> $dominionIds
     */
    private function seedSpells(array $dominionIds): void
    {
        [$beneficial, $harmful] = $this->pickSpells();

        $now = now();
        $rows = [];

        foreach ($dominionIds as $dominionId) {
            foreach ($beneficial as $offset => $spell) {
                $rows[] = [
                    'dominion_id' => $dominionId,
                    'spell_id' => $spell->id,
                    'duration' => self::BENEFICIAL_DURATIONS[$offset],
                    'cast_by_dominion_id' => $dominionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach ($harmful as $offset => $spell) {
                $rows[] = [
                    'dominion_id' => $dominionId,
                    'spell_id' => $spell->id,
                    'duration' => self::HARMFUL_DURATIONS[$offset],
                    'cast_by_dominion_id' => $dominionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('dominion_spells')->insert($chunk);
        }
    }

    /**
     * Prefers the spells carrying the most perks - the perk fan-out in finding
     * 2.3 is proportional to perk count, so the cheapest spells would understate
     * it.
     *
     * @return array{0: Collection<int, Spell>, 1: Collection<int, Spell>}
     */
    private function pickSpells(): array
    {
        $spells = Spell::withCount('perks')
            ->orderByDesc('perks_count')
            ->orderBy('id')
            ->get();

        $spells = $spells->reject(
            fn (Spell $spell): bool => in_array($spell->key, self::FACTORY_SEEDED_SPELL_KEYS, true)
        );

        $beneficial = $spells->reject(fn (Spell $spell): bool => $spell->isHarmful())
            ->take(count(self::BENEFICIAL_DURATIONS))
            ->values();

        $harmful = $spells->filter(fn (Spell $spell): bool => $spell->isHarmful())
            ->take(count(self::HARMFUL_DURATIONS))
            ->values();

        if ($beneficial->count() < count(self::BENEFICIAL_DURATIONS) || $harmful->count() < count(self::HARMFUL_DURATIONS)) {
            throw new RuntimeException('Not enough spells in the database to build the benchmark fixture.');
        }

        return [$beneficial, $harmful];
    }

    /**
     * Establishes the dominion_tick rows that phase A of the tick joins against.
     * Without this the bulk update matches nothing and the measurement is
     * meaningless.
     *
     * @param array<int, int> $dominionIds
     */
    private function precalculate(array $dominionIds): void
    {
        $dominions = Dominion::whereIn('id', $dominionIds)
            ->with(['queues', 'spells', 'techs', 'race.units.perks', 'race.perks'])
            ->get();

        foreach ($dominions as $dominion) {
            $this->tickService->precalculateTick($dominion);
        }
    }

    /**
     * Seeds mt_rand, which fixes every structural property the benchmark
     * measures: the number of realms, dominions, queue rows and spell rows.
     *
     * createNonPlayer also calls random_chance(), which is backed by random_int
     * and cannot be seeded. That is left alone on purpose. It varies unit counts
     * and building mix - realistic dominion variety - but not row counts, so
     * query counts stay reproducible while wall time wobbles slightly. Pinning it
     * through the codebase's $mockRandomChance global would force every dominion
     * to zero elites and zero factories, which is a worse fixture.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function withDeterministicRandomness(callable $callback)
    {
        mt_srand(self::RANDOM_SEED);

        try {
            return $callback();
        } finally {
            mt_srand();
        }
    }
}
