<?php

namespace OpenDominion\Tests\Unit\Helpers;

use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SpellCasterVisibilityTest extends AbstractTestCase
{
    public static function perceptionSources(): array
    {
        return [
            'Surreal Perception' => [1, 0],
            'Astral Panopticon' => [0, 1],
        ];
    }

    #[DataProvider('perceptionSources')]
    public function testOnlyLiveAdvisorsCanUseTheTargetsPerception(float $spellPerk, float $wonderPerk): void
    {
        $realm = (new Realm())->forceFill(['number' => 3]);
        $target = $this->getMockBuilder(Dominion::class)
            ->onlyMethods(['getSpellPerkValue', 'getWonderPerkValue'])->getMock();
        $target->method('getSpellPerkValue')->with('surreal_perception')->willReturn($spellPerk);
        $target->method('getWonderPerkValue')->with('surreal_perception')->willReturn($wonderPerk);
        $target->forceFill(['id' => 10, 'realm_id' => 15, 'round_id' => 2]);
        $target->setRelation('realm', $realm);
        $viewer = (new Dominion())->forceFill(['id' => 20, 'realm_id' => 15, 'round_id' => 2]);
        $viewer->setRelation('realm', $realm);
        $spells = [[
            'spell' => 'plague', 'duration' => 6,
            'cast_by_dominion_id' => 30, 'cast_by_dominion_name' => 'Caster', 'cast_by_dominion_realm_number' => 7,
        ]];
        $helper = new SpellHelper();

        $this->assertSame($spells, $helper->visibleRevelationSpells($spells, $viewer, $target, true));
        $this->assertNull($helper->visibleRevelationSpells($spells, $viewer, $target)[0]['cast_by_dominion_id']);

        $viewer->realm_id = 18;
        $viewer->setRelation('realm', (new Realm())->forceFill(['number' => 4]));
        $this->assertNull($helper->visibleRevelationSpells($spells, $viewer, $target, true)[0]['cast_by_dominion_id']);
    }

    public function testRevelationOnlyShowsCastersFromTheViewersRealmAndNeverExportsNestedDominions(): void
    {
        $realm = (new Realm())->forceFill(['number' => 3]);
        $viewer = (new Dominion())->forceFill(['id' => 20, 'name' => 'Viewer', 'realm_id' => 13, 'round_id' => 2]);
        $viewer->setRelation('realm', $realm);
        $target = (new Dominion())->forceFill(['id' => 10, 'name' => 'Target', 'realm_id' => 44, 'round_id' => 2]);
        $target->setRelation('realm', (new Realm())->forceFill(['number' => 4]));

        $spells = [];
        foreach ([[10, 'Target', 4], [20, 'Viewer', 3], [30, 'Third party', 9], [null, null, null]] as [$casterId, $name, $realmNumber]) {
            $spells[] = [
                'spell' => 'plague', 'duration' => 6, 'cast_by_dominion_id' => $casterId,
                'cast_by_dominion_name' => $name, 'cast_by_dominion_realm_number' => $realmNumber,
                'cast_by_dominion' => ['id' => $casterId, 'name' => 'Private caster data'],
            ];
        }

        $visible = (new SpellHelper())->visibleRevelationSpells($spells, $viewer, $target);
        $this->assertSame([10, 20, null, null], array_column($visible, 'cast_by_dominion_id'));
        $this->assertSame(['Target', 'Viewer', null, null], array_column($visible, 'cast_by_dominion_name'));
        $this->assertSame([4, 3, null, null], array_column($visible, 'cast_by_dominion_realm_number'));
        $viewer->round_id = 3;
        $foreign = (new SpellHelper())->visibleRevelationSpells($spells, $viewer, $target);
        $this->assertSame([null, null, null, null], array_column($foreign, 'cast_by_dominion_id'));
        foreach ($visible as $spell) {
            $this->assertArrayNotHasKey('cast_by_dominion', $spell);
        }
    }
}
