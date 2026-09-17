<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Models\SpellPerkType;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Every spell category can have instant and duration spells. Instant spells
 * resolve their perks through the shared resolver and never leave a
 * dominion_spells row behind.
 */
class InstantSpellsTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var string[] Perk prefixes the shared resolver treats as instant effects */
    protected const INSTANT_PERK_PREFIXES = ['destroy_', 'convert_', 'reduce_duration_', 'repair_', 'revive_'];

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var SpellHelper */
    protected $spellHelper;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var Dominion */
    protected $realmmate;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Human')->firstOrFail());

        $realmmateUser = $this->createUser();
        $this->realmmate = $this->createDominionWithLegacyStats(
            $realmmateUser,
            $this->round,
            Race::where('name', 'Human')->firstOrFail(),
            $this->dominion->realm
        );

        $this->spellActionService = $this->app->make(SpellActionService::class);
        $this->spellHelper = $this->app->make(SpellHelper::class);

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    /**
     * Creates a spell that only exists for this test.
     */
    protected function createSpell(string $key, string $category, int $duration, array $perks): Spell
    {
        $spell = Spell::create([
            'key' => $key,
            'name' => ucwords(str_replace('_', ' ', $key)),
            'category' => $category,
            'school' => 'transmutation',
            'cost_mana' => 1,
            'cost_strength' => 5,
            'duration' => $duration,
            'races' => [],
            'active' => true,
        ]);

        foreach ($perks as $perkKey => $value) {
            $perkType = SpellPerkType::firstOrCreate(['key' => $perkKey]);
            $spell->perks()->attach($perkType->id, ['value' => $value]);
        }

        Cache::forget('game:spells');

        return $spell->fresh('perks');
    }

    public function testInstantSpellsAreRecognizedByDuration(): void
    {
        $this->assertTrue(
            $this->spellHelper->isInstantSpell(Spell::where('key', 'disband_spies')->firstOrFail()),
            'Disband Spies has no duration and should be instant'
        );
        $this->assertFalse(
            $this->spellHelper->isInstantSpell(Spell::where('key', 'plague')->firstOrFail()),
            'Plague lasts 8 hours and should not be instant'
        );
    }

    public function testInstantSelfSpellResolvesImmediately(): void
    {
        $this->createSpell('test_instant_self', 'self', 0, ['destroy_resource_food' => 10]);

        $this->dominion->resource_mana = 100000;
        $this->dominion->resource_food = 50000;

        $result = $this->spellActionService->castSpell($this->dominion, 'test_instant_self');

        $this->assertEquals(45000, $this->dominion->resource_food);
        $this->assertStringContainsString('cast the spell successfully', $result['message']);
        $this->assertEquals(0, DominionSpell::where('dominion_id', $this->dominion->id)->count());
    }

    public function testDurationSelfSpellStillLasts(): void
    {
        $this->createSpell('test_duration_self', 'self', 12, ['food_production' => 10]);

        $this->dominion->resource_mana = 100000;

        $this->spellActionService->castSpell($this->dominion, 'test_duration_self');

        $activeSpell = DominionSpell::where('dominion_id', $this->dominion->id)->first();
        $this->assertNotNull($activeSpell, 'A duration self spell should still be stored');
        $this->assertEquals(12, $activeSpell->duration);
    }

    public function testInstantFriendlySpellResolvesOnTheTarget(): void
    {
        $spell = $this->createSpell('test_instant_friendly', 'friendly', 0, [
            'convert_peasants_to_military_draftees' => 5,
        ]);

        $this->dominion->resource_mana = 100000;
        $this->dominion->realm->magister_dominion_id = $this->dominion->id;
        $this->dominion->realm->save();
        $this->realmmate->peasants = 10000;
        $this->realmmate->military_draftees = 0;
        $this->realmmate->save();

        $this->spellActionService->castSpell($this->dominion, $spell->key, $this->realmmate);

        $this->assertEquals(9500, $this->realmmate->peasants);
        $this->assertEquals(500, $this->realmmate->military_draftees);
        $this->assertEquals(
            0,
            DominionSpell::where('dominion_id', $this->realmmate->id)->count(),
            'An instant friendly spell should not leave an active spell behind'
        );
    }

    public function testDurationFriendlySpellStillLasts(): void
    {
        $this->dominion->resource_mana = 100000;
        $this->dominion->realm->magister_dominion_id = $this->dominion->id;
        $this->dominion->realm->save();

        $this->spellActionService->castSpell($this->dominion, 'arcane_ward', $this->realmmate);

        $activeSpell = DominionSpell::where('dominion_id', $this->realmmate->id)->first();
        $this->assertNotNull($activeSpell, 'A duration friendly spell should still be stored');
        $this->assertEquals(6, $activeSpell->duration);
    }

    /**
     * An instant spell with no instant perks would silently do nothing.
     */
    public function testEveryInstantSpellDeclaresAnInstantEffect(): void
    {
        $spellData = Yaml::parse(file_get_contents(base_path('app/data/spells.yml')));

        foreach ($spellData as $spellKey => $data) {
            if (!in_array($data['category'], ['self', 'friendly', 'hostile', 'war'], true)) {
                continue;
            }

            if (!empty($data['duration'])) {
                continue;
            }

            $instantPerks = array_filter(
                array_keys($data['perks'] ?? []),
                static function (string $perkKey) {
                    foreach (static::INSTANT_PERK_PREFIXES as $prefix) {
                        if (str_starts_with($perkKey, $prefix)) {
                            return true;
                        }
                    }
                    return false;
                }
            );

            $this->assertNotEmpty(
                $instantPerks,
                "Spell {$spellKey} has no duration and no instant effect perk"
            );
        }
    }
}
