<?php

namespace OpenDominion\Tests\Feature;

use OpenDominion\Models\Spell;
use OpenDominion\Tests\AbstractTestCase;
use Symfony\Component\Yaml\Yaml;

class SpellSchoolTest extends AbstractTestCase
{
    /** @var string[] */
    protected const SCHOOLS = [
        'abjuration',
        'conjuration',
        'divination',
        'enchantment',
        'evocation',
        'illusion',
        'necromancy',
        'transmutation',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getSpellData(): array
    {
        return Yaml::parse(file_get_contents(base_path('app/data/spells.yml')));
    }

    public function testEverySpellInYamlDeclaresAKnownSchool(): void
    {
        foreach ($this->getSpellData() as $spellKey => $spellData) {
            $this->assertArrayHasKey('school', $spellData, "Spell {$spellKey} is missing a school");

            if ($spellData['school'] === null) {
                continue;
            }

            $this->assertContains(
                $spellData['school'],
                self::SCHOOLS,
                "Spell {$spellKey} has an unknown school '{$spellData['school']}'"
            );
        }
    }

    /**
     * A null school means the effect has no magical origin, which only status
     * effects can claim. Anything castable must name the school it belongs to.
     */
    public function testOnlyStatusEffectsMayHaveANullSchool(): void
    {
        foreach ($this->getSpellData() as $spellKey => $spellData) {
            if ($spellData['school'] !== null) {
                continue;
            }

            $this->assertSame(
                'effect',
                $spellData['category'],
                "Spell {$spellKey} has no school but is not a status effect"
            );
        }
    }

    public function testDataSyncPersistsSchoolsToTheDatabase(): void
    {
        $this->artisan('game:data:sync')->assertExitCode(0);

        $spellData = $this->getSpellData();
        $spells = Spell::all()->keyBy('key');

        foreach ($spellData as $spellKey => $data) {
            $this->assertNotNull($spells->get($spellKey), "Spell {$spellKey} was not synced");
            $this->assertSame(
                $data['school'],
                $spells->get($spellKey)->school,
                "Spell {$spellKey} did not sync its school"
            );
        }
    }

    public function testDataSyncUpdatesTheSchoolOfAnExistingSpell(): void
    {
        $this->artisan('game:data:sync')->assertExitCode(0);

        $spell = Spell::where('key', 'fireball')->firstOrFail();
        $spell->school = 'illusion';
        $spell->save();

        $this->artisan('game:data:sync')->assertExitCode(0);

        $this->assertSame('evocation', $spell->fresh()->school);
    }
}
