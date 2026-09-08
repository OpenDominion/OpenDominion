<?php

namespace OpenDominion\Tests\Unit\Models;

use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Tech;
use OpenDominion\Tests\AbstractTestCase;
use ReflectionProperty;

/**
 * Guards the memoized perk resolution on Dominion.
 *
 * Two things are being protected here:
 *
 *  1. That memoizing did not change any answer. getSpellPerkValue and
 *     getTechPerkValue were rewritten to read from a grouped structure built
 *     once per relation load instead of rebuilding a collection on every call.
 *     The tests below recompute every value the way the old implementation did
 *     and demand an exact match, across every perk key in the seeded game data.
 *
 *  2. That the memo dies with the relation it describes. There is no manual
 *     invalidation call anywhere - correctness depends entirely on the
 *     setRelation/unsetRelation/setRelations/unsetRelations/__clone overrides
 *     firing, so each is tested directly.
 */
class DominionPerkResolutionTest extends AbstractTestCase
{
    /**
     * The distinct $types arrays actually passed to getSpellPerkValue anywhere
     * in src/, including the five single-category lookups that
     * SpellCalculator::resolveSpellPerk makes.
     *
     * @var array<int, array<int, string>>
     */
    private const TYPE_SETS = [
        ['self', 'friendly'],
        ['self'],
        ['hostile'],
        ['war'],
        ['friendly'],
        ['effect'],
        ['self', 'friendly', 'effect'],
        ['self', 'friendly', 'hostile', 'war'],
    ];

    public function testSpellPerkValueMatchesPreviousImplementationForEveryKeyAndTypeSet(): void
    {
        $dominion = $this->dominionWithEverySpell();

        $keys = $this->allSpellPerkKeys($dominion);
        $this->assertNotEmpty($keys, 'Expected the seeded game data to contain spell perks.');

        $comparisons = 0;

        foreach ($keys as $key) {
            foreach (self::TYPE_SETS as $types) {
                $this->assertSame(
                    $this->legacySpellPerkValue($dominion, $key, $types),
                    $dominion->getSpellPerkValue($key, $types),
                    sprintf('Spell perk "%s" for types [%s] changed value.', $key, implode(', ', $types))
                );

                $comparisons++;
            }
        }

        // A miscounted fixture that silently resolves nothing would pass every
        // assertion above, so assert the comparison actually had breadth.
        $this->assertGreaterThan(100, $comparisons);
    }

    /**
     * The non-stacking rule flips from max to min when the max is negative.
     * That branch only runs for a key whose values are all below zero, so it is
     * worth proving the fixture reaches it rather than assuming.
     */
    public function testNegativeValuedSpellPerkStillResolvesThroughTheMinBranch(): void
    {
        $dominion = $this->dominionWithEverySpell();

        $negativeKey = null;

        foreach ($this->allSpellPerkKeys($dominion) as $key) {
            if ($this->legacySpellPerkValue($dominion, $key, ['self', 'friendly']) < 0) {
                $negativeKey = $key;
                break;
            }
        }

        if ($negativeKey === null) {
            $this->markTestSkipped('No negative-valued spell perk in the seeded data.');
        }

        $this->assertSame(
            $this->legacySpellPerkValue($dominion, $negativeKey, ['self', 'friendly']),
            $dominion->getSpellPerkValue($negativeKey, ['self', 'friendly'])
        );
    }

    public function testUnknownSpellPerkKeyResolvesToZero(): void
    {
        $dominion = $this->dominionWithEverySpell();

        $this->assertSame(0.0, $dominion->getSpellPerkValue('this_perk_does_not_exist'));
    }

    public function testTechPerkValueMatchesPreviousImplementationForEveryKey(): void
    {
        $dominion = $this->dominionWithEveryTech();

        $legacy = $dominion->techs
            ->flatMap(static function (Tech $tech): Collection {
                return $tech->perks;
            })
            ->groupBy('key');

        $this->assertNotEmpty($legacy, 'Expected the seeded game data to contain tech perks.');

        foreach ($legacy as $key => $perks) {
            $this->assertSame(
                (float)$perks->sum('pivot.value'),
                $dominion->getTechPerkValue((string)$key),
                sprintf('Tech perk "%s" changed value.', $key)
            );
        }
    }

    public function testUnsetRelationInvalidatesTheCache(): void
    {
        $dominion = $this->dominionWithEverySpell();
        $key = $this->firstResolvingSpellPerkKey($dominion);

        $this->assertNotSame(0.0, $dominion->getSpellPerkValue($key, ['self', 'friendly']));

        $dominion->unsetRelation('spells');
        $dominion->setRelation('spells', collect([]));

        $this->assertSame(0.0, $dominion->getSpellPerkValue($key, ['self', 'friendly']));
    }

    public function testSetRelationInvalidatesTheCache(): void
    {
        $dominion = $this->dominionWithEverySpell();
        $key = $this->firstResolvingSpellPerkKey($dominion);

        $this->assertNotSame(0.0, $dominion->getSpellPerkValue($key, ['self', 'friendly']));

        $dominion->setRelation('spells', collect([]));

        $this->assertSame(0.0, $dominion->getSpellPerkValue($key, ['self', 'friendly']));
    }

    /**
     * setRelations() and unsetRelations() assign $this->relations directly and
     * never call setRelation(), so they need their own hooks. Missing these is
     * the usual hole in this pattern.
     */
    public function testBulkRelationMutatorsInvalidateTheCache(): void
    {
        $dominion = $this->dominionWithEverySpell();
        $key = $this->firstResolvingSpellPerkKey($dominion);

        $dominion->getSpellPerkValue($key, ['self', 'friendly']);
        $dominion->setRelations(['spells' => collect([])]);
        $this->assertSame(0.0, $dominion->getSpellPerkValue($key, ['self', 'friendly']));

        $dominion = $this->dominionWithEverySpell();
        $dominion->getSpellPerkValue($key, ['self', 'friendly']);
        $dominion->unsetRelations();
        $dominion->setRelation('spells', collect([]));
        $this->assertSame(0.0, $dominion->getSpellPerkValue($key, ['self', 'friendly']));
    }

    /**
     * PHP's shallow clone would otherwise hand the copy a cache describing the
     * original's relations. DominionSaved clones a dominion and then reloads
     * relations on the copy, which is exactly this situation.
     */
    public function testCloneStartsWithAnEmptyCache(): void
    {
        $dominion = $this->dominionWithEverySpell();
        $key = $this->firstResolvingSpellPerkKey($dominion);

        $dominion->getSpellPerkValue($key, ['self', 'friendly']);
        $this->assertNotNull($this->readCache($dominion), 'Expected the original to have a populated cache.');

        $clone = clone $dominion;

        $this->assertNull($this->readCache($clone), 'Clone inherited the original perk cache.');
        $this->assertNotNull($this->readCache($dominion), 'Cloning must not disturb the original.');
    }

    /**
     * The cache is not keyed on the dominion id, and this is why: the
     * calculations page builds a Dominion that has never been saved.
     */
    public function testUnsavedDominionsResolveIndependently(): void
    {
        $withSpells = $this->dominionWithEverySpell();
        $key = $this->firstResolvingSpellPerkKey($withSpells);

        $unsaved = new Dominion();
        $unsaved->setRelation('spells', collect([]));

        $this->assertNull($unsaved->id);
        $this->assertSame(0.0, $unsaved->getSpellPerkValue($key, ['self', 'friendly']));
        $this->assertNotSame(0.0, $withSpells->getSpellPerkValue($key, ['self', 'friendly']));
    }

    /**
     * Reproduces the pre-memoization algorithm exactly, using the retained (now
     * deprecated) getSpellPerks() as its input.
     */
    private function legacySpellPerkValue(Dominion $dominion, string $key, array $types): float
    {
        $perks = $dominion->getSpellPerks()->whereIn('category', $types)->groupBy('key');

        if (isset($perks[$key])) {
            if ($perks[$key]->count() == 1) {
                return $perks[$key]->first()->pivot->value;
            }

            $perkValue = (float)$perks[$key]->max('pivot.value');

            if ($perkValue < 0) {
                $perkValue = (float)$perks[$key]->min('pivot.value');
            }

            return $perkValue;
        }

        return 0;
    }

    /**
     * @return array<int, string>
     */
    private function allSpellPerkKeys(Dominion $dominion): array
    {
        return $dominion->getSpellPerks()
            ->pluck('key')
            ->unique()
            ->values()
            ->all();
    }

    private function firstResolvingSpellPerkKey(Dominion $dominion): string
    {
        foreach ($this->allSpellPerkKeys($dominion) as $key) {
            if ($this->legacySpellPerkValue($dominion, $key, ['self', 'friendly']) != 0.0) {
                return $key;
            }
        }

        $this->fail('No spell perk in the seeded data resolves to a non-zero self/friendly value.');
    }

    /**
     * Attaches every spell that carries perks, so the comparison covers every
     * category present in the game data - including keys that appear under more
     * than one category, which is where grouping could go wrong.
     */
    private function dominionWithEverySpell(): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $this->createRound('-7 days'));

        $attachments = Spell::has('perks')->pluck('id')->mapWithKeys(
            static function (int $spellId): array {
                return [$spellId => ['duration' => 12]];
            }
        )->all();

        $dominion->spells()->attach($attachments);
        $dominion->load(['spells', 'spells.perks']);

        return $dominion;
    }

    private function dominionWithEveryTech(): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $this->createRound('-7 days'));

        $dominion->techs()->syncWithoutDetaching(Tech::has('perks')->pluck('id')->all());
        $dominion->load(['techs', 'techs.perks']);

        return $dominion;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readCache(Dominion $dominion): ?array
    {
        $property = new ReflectionProperty(Dominion::class, 'spellPerksByCategory');
        $property->setAccessible(true);

        return $property->getValue($dominion);
    }
}
