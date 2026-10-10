<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Content\Ai\WeightedStrategy;
use OpenDominion\HeroCombat\Engine\EncounterContext;
use OpenDominion\HeroCombat\Engine\Random\SeededRandomSource;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Providers\HeroCombatServiceProvider;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards the content catalog: every key referenced anywhere resolves, and every encounter
 * can be fought to a finish.
 */
class EncounterCatalogTest extends TestCase
{
    use BuildsBattles;

    public const PRACTICE_ENCOUNTERS = [
        'default', 'rabid_bunny', 'dragonkin', 'gate_warden', 'rebel_corsair', 'rebel_admiral',
        'fallen_kings', 'eternal_guardian', 'nightbringer', 'lich_king', 'planewalker_golems',
        'planewalker', 'wraith', 'dreadsoul_skullkeeper', 'dream_of_thessadrash', 'heart_of_ice',
        'admiral_varos', 'rex_lunae',
    ];

    private static function registry(): CombatRegistry
    {
        return HeroCombatServiceProvider::buildRegistry();
    }

    public function testEveryOriginalEncounterKeyIsRegistered(): void
    {
        $this->assertEquals(self::PRACTICE_ENCOUNTERS, array_keys(self::registry()->encounters()));
    }

    public function testEveryReferencedKeyResolves(): void
    {
        $registry = self::registry();

        foreach ($registry->encounters() as $encounter) {
            foreach ($encounter->roster(new EncounterContext(priorWins: 3)) as $entry) {
                $template = $registry->enemy($entry['template']);
                foreach ($template->abilities() as $ability) {
                    $this->assertTrue($registry->hasAbility($ability), "{$template->key()} ability {$ability}");
                }
                foreach (array_keys(array_merge($template->effects(), $entry['effects'] ?? [])) as $effect) {
                    $this->assertTrue($registry->hasEffect($effect), "{$template->key()} effect {$effect}");
                }
                $this->assertTrue($registry->hasStrategy($template->ai()), "{$template->key()} ai {$template->ai()}");
            }
        }

        foreach (WeightedStrategy::presets() as $strategy) {
            foreach (array_keys($strategy->weights()) as $ability) {
                $this->assertTrue($registry->hasAbility($ability), "{$strategy->key()} weight {$ability}");
            }
        }
    }

    public static function encounters(): array
    {
        return array_map(fn ($key) => [$key], array_combine(self::PRACTICE_ENCOUNTERS, self::PRACTICE_ENCOUNTERS));
    }

    #[DataProvider('encounters')]
    public function testEncounterCanBeFoughtToTheEnd(string $encounterKey): void
    {
        foreach ([11, 22, 33] as $seed) {
            $battle = BattleBuilder::make($encounterKey)->withRoster()->hero('Alice')->hero('Bob')->build();
            foreach ($battle->combatants() as $combatant) {
                $combatant->automated = true;
            }

            for ($i = 0; $i < 5 && !$battle->state->finished; $i++) {
                $this->engine()->advance($battle, function ($battle) use ($seed) {
                    $battle->random = SeededRandomSource::forTurn($seed, $battle->turn());
                });
            }

            $this->assertTrue($battle->state->finished, "{$encounterKey} (seed {$seed}) did not finish");
        }
    }
}
