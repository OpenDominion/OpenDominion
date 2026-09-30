<?php

namespace OpenDominion\Tests\Unit\Mappers;

use Illuminate\Database\Eloquent\Model;
use OpenDominion\Mappers\GameEventMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Tests\AbstractTestCase;
use stdClass;

class GameEventMapperTest extends AbstractTestCase
{
    private GameEventMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new GameEventMapper();
    }

    public function testPublicMorphTypeIsSnakeCasedBasename(): void
    {
        $this->assertSame('dominion', $this->mapper->getPublicMorphType(Dominion::class));
        $this->assertSame('realm_war', $this->mapper->getPublicMorphType(RealmWar::class));
        $this->assertSame('raid_objective_tactic', $this->mapper->getPublicMorphType(RaidObjectiveTactic::class));
        $this->assertNull($this->mapper->getPublicMorphType(null));
        $this->assertNull($this->mapper->getPublicMorphType(''));
    }

    public function testMappedEventOnlyHasWhitelistedKeys(): void
    {
        $mapped = $this->mapper->mapPublic($this->makeEvent('invasion', ['result' => ['success' => false]]));

        $this->assertSame(
            [
                'id', 'type',
                'source_type', 'source_id', 'source_name', 'source_realm_number',
                'target_type', 'target_id', 'target_name', 'target_realm_number',
                'data', 'created_at',
            ],
            array_keys($mapped)
        );
    }

    public function testSuccessfulInvasionExposesOnlySuccessAndLandLost(): void
    {
        $event = $this->makeEvent('invasion', [
            'result' => ['success' => true, 'overwhelmed' => false, 'range' => 82.5],
            'attacker' => [
                'landConquered' => ['plain' => 10, 'forest' => 5],
                'landGenerated' => ['plain' => 4],
                'landGained' => 19,
                'unitsSent' => [1 => 500],
                'unitsLost' => [1 => 40],
            ],
            'defender' => ['unitsLost' => [2 => 100]],
        ]);

        $this->assertSame(
            ['success' => true, 'land_lost' => 15, 'land_gained' => 19],
            $this->mapper->mapPublic($event)['data']
        );
    }

    public function testInvasionWithoutStoredLandGainedSumsConqueredAndGeneratedLand(): void
    {
        $event = $this->makeEvent('invasion', [
            'result' => ['success' => true],
            'attacker' => [
                'landConquered' => ['plain' => 10, 'forest' => 5],
                'landGenerated' => ['plain' => 4, 'forest' => 2],
            ],
        ]);

        $this->assertSame(
            ['success' => true, 'land_lost' => 15, 'land_gained' => 21],
            $this->mapper->mapPublic($event)['data']
        );
    }

    public function testFailedInvasionReportsNoLandLost(): void
    {
        $event = $this->makeEvent('invasion', [
            'result' => ['success' => false, 'overwhelmed' => true],
            'attacker' => ['unitsLost' => [1 => 400]],
        ]);

        $this->assertSame(
            ['success' => false, 'land_lost' => 0, 'land_gained' => 0],
            $this->mapper->mapPublic($event)['data']
        );
    }

    public function testWarDeclaredUsesRealmNamesFromStartOfWar(): void
    {
        $war = $this->makeRealmWar();
        $event = $this->makeEvent('war_declared', ['monarchDominionID' => 99], $this->makeRealm(3, 'Current Source'), $war);

        $this->assertSame([
            'source_realm' => ['number' => 3, 'name' => 'Source At Start'],
            'target_realm' => ['number' => 7, 'name' => 'Target At Start'],
        ], $this->mapper->mapPublic($event)['data']);
    }

    public function testWarCanceledUsesRealmNamesFromEndOfWar(): void
    {
        $war = $this->makeRealmWar();
        $event = $this->makeEvent('war_canceled', null, $this->makeRealm(3, 'Current Source'), $war);

        $this->assertSame([
            'source_realm' => ['number' => 3, 'name' => 'Source At End'],
            'target_realm' => ['number' => 7, 'name' => 'Target At End'],
        ], $this->mapper->mapPublic($event)['data']);
    }

    public function testLegacyWarEventsBetweenRealmsAreMapped(): void
    {
        $event = $this->makeEvent('war_declared', [], $this->makeRealm(3, 'Source'), $this->makeRealm(7, 'Target'));

        $this->assertSame([
            'source_realm' => ['number' => 3, 'name' => 'Source'],
            'target_realm' => ['number' => 7, 'name' => 'Target'],
        ], $this->mapper->mapPublic($event)['data']);
    }

    public function testWonderSpawnedExposesOnlyWonderName(): void
    {
        $wonder = $this->makeWonder('Ivory Tower');
        $event = $this->makeEvent('wonder_spawned', ['power' => 250000], $wonder, $wonder);

        $this->assertSame(['wonder' => 'Ivory Tower'], $this->mapper->mapPublic($event)['data']);
    }

    public function testNeutralWonderAttackHidesWhichWonderWasAttacked(): void
    {
        $event = $this->makeEvent(
            'wonder_attacked',
            ['wonder' => ['neutral' => true, 'power' => 1000], 'attacker' => ['unitsSent' => [1 => 50]]],
            new Dominion(),
            $this->makeRoundWonder('Ivory Tower', null)
        );
        $event->target_id = 12;

        $mapped = $this->mapper->mapPublic($event);

        $this->assertNull($mapped['target_id']);
        $this->assertNull($mapped['target_name']);
        $this->assertNull($mapped['target_realm_number']);
        $this->assertSame(['neutral' => true, 'wonder' => null, 'realm_number' => null], $mapped['data']);
    }

    public function testDominionAndRealmParticipantsIncludeNameAndRealmNumber(): void
    {
        $attacker = new Dominion();
        $attacker->name = 'Attacker';
        $attacker->setRelation('realm', $this->makeRealm(3, 'Aggressors'));
        $defender = new Dominion();
        $defender->name = 'Defender';
        $defender->setRelation('realm', $this->makeRealm(7, 'Defenders'));

        $invasion = $this->mapper->mapPublic($this->makeEvent('invasion', ['result' => ['success' => false]], $attacker, $defender));

        $this->assertSame('Attacker', $invasion['source_name']);
        $this->assertSame(3, $invasion['source_realm_number']);
        $this->assertSame('Defender', $invasion['target_name']);
        $this->assertSame(7, $invasion['target_realm_number']);

        $war = $this->mapper->mapPublic($this->makeEvent('war_declared', [], $this->makeRealm(3, 'Aggressors'), $this->makeRealmWar()));

        $this->assertSame('Aggressors', $war['source_name']);
        $this->assertSame(3, $war['source_realm_number']);
        $this->assertNull($war['target_name'], 'Realm wars are described in data, not by name.');
        $this->assertNull($war['target_realm_number']);
    }

    public function testWonderAttackWithoutNeutralFlagIsTreatedAsNeutral(): void
    {
        $event = $this->makeEvent('wonder_attacked', [], new Dominion(), $this->makeRoundWonder('Ivory Tower', null));
        $event->target_id = 12;

        $mapped = $this->mapper->mapPublic($event);

        $this->assertNull($mapped['target_id']);
        $this->assertTrue($mapped['data']['neutral']);
    }

    public function testOwnedWonderAttackExposesWonderAndOwningRealm(): void
    {
        $event = $this->makeEvent(
            'wonder_attacked',
            ['wonder' => ['neutral' => false, 'power' => 1000, 'currentRealmId' => 44]],
            new Dominion(),
            $this->makeRoundWonder('Ivory Tower', $this->makeRealm(7, 'Owners'))
        );
        $event->target_id = 12;

        $mapped = $this->mapper->mapPublic($event);

        $this->assertSame(12, $mapped['target_id']);
        $this->assertSame(['neutral' => false, 'wonder' => 'Ivory Tower', 'realm_number' => 7], $mapped['data']);
    }

    public function testWonderDestroyedAndRebuiltExposesWonderAndRealm(): void
    {
        $event = $this->makeEvent(
            'wonder_destroyed',
            ['destroyedByRealmId' => 44, 'damageBreakdown' => [['damage_total' => 500]], 'power' => 1000],
            $this->makeRoundWonder('Ivory Tower', null),
            $this->makeRealm(7, 'Victors')
        );

        $this->assertSame([
            'wonder' => 'Ivory Tower',
            'rebuilt_by_realm' => ['number' => 7, 'name' => 'Victors'],
        ], $this->mapper->mapPublic($event)['data']);
    }

    public function testWonderDestroyedWithoutRebuildHasNullRealm(): void
    {
        $event = $this->makeEvent('wonder_destroyed', [], $this->makeRoundWonder('Ivory Tower', null));

        $this->assertSame(
            ['wonder' => 'Ivory Tower', 'rebuilt_by_realm' => null],
            $this->mapper->mapPublic($event)['data']
        );
    }

    public function testSentientWonderInvasionIsNotAPublicTypeAndExposesNoData(): void
    {
        $event = $this->makeEvent(
            'wonder_invasion',
            ['landLost' => 23, 'land' => ['plain' => 23]],
            $this->makeRoundWonder('Ivory Tower', null),
            new Dominion()
        );

        $this->assertNotContains('wonder_invasion', GameEventMapper::PUBLIC_TYPES);
        $this->assertSame('{}', json_encode($this->mapper->mapPublic($event)['data']));
    }

    public function testEveryPublicTypeIsHandledByTheTownCrierPartial(): void
    {
        $partial = file_get_contents(resource_path('views/partials/dominion/game-event.blade.php'));

        foreach (GameEventMapper::PUBLIC_TYPES as $type) {
            $this->assertStringContainsString("'" . $type . "'", $partial);
        }
    }

    public function testRaidAttackedExposesOnlyTacticName(): void
    {
        $tactic = new RaidObjectiveTactic();
        $tactic->name = 'Storm the Gates';
        $event = $this->makeEvent('raid_attacked', ['damageDealt' => 1234, 'unitsLost' => [1 => 5]], new Dominion(), $tactic);

        $this->assertSame(['tactic' => 'Storm the Gates'], $this->mapper->mapPublic($event)['data']);
    }

    public function testAbandonedHasEmptyDataObject(): void
    {
        $mapped = $this->mapper->mapPublic($this->makeEvent('abandoned', null, new Dominion()));

        $this->assertInstanceOf(stdClass::class, $mapped['data']);
        $this->assertSame('{}', json_encode($mapped['data']));
    }

    public function testUnknownEventTypesExposeNoData(): void
    {
        $mapped = $this->mapper->mapPublic($this->makeEvent('some_future_type', ['secret' => 'private']));

        $this->assertSame('{}', json_encode($mapped['data']));
    }

    public function testMissingRelatedModelsDoNotBreakMapping(): void
    {
        $this->assertSame(
            ['source_realm' => null, 'target_realm' => null],
            $this->mapper->mapPublic($this->makeEvent('war_declared', []))['data']
        );
        $this->assertSame(['wonder' => null], $this->mapper->mapPublic($this->makeEvent('wonder_spawned', []))['data']);
        $this->assertSame(['tactic' => null], $this->mapper->mapPublic($this->makeEvent('raid_attacked', []))['data']);
    }

    private function makeEvent(string $type, ?array $data, ?Model $source = null, ?Model $target = null): GameEvent
    {
        $event = new GameEvent();
        $event->id = 'event-id';
        $event->type = $type;
        $event->data = $data;
        $event->source_type = $source === null ? null : get_class($source);
        $event->source_id = 1;
        $event->target_type = $target === null ? null : get_class($target);
        $event->target_id = $target === null ? null : 2;
        $event->setRelation('source', $source);
        $event->setRelation('target', $target);

        return $event;
    }

    private function makeRealm(int $number, string $name): Realm
    {
        $realm = new Realm();
        $realm->number = $number;
        $realm->name = $name;

        return $realm;
    }

    private function makeRealmWar(): RealmWar
    {
        $war = new RealmWar();
        $war->source_realm_name_start = 'Source At Start';
        $war->target_realm_name_start = 'Target At Start';
        $war->source_realm_name_end = 'Source At End';
        $war->target_realm_name_end = 'Target At End';
        $war->setRelation('sourceRealm', $this->makeRealm(3, 'Current Source'));
        $war->setRelation('targetRealm', $this->makeRealm(7, 'Current Target'));

        return $war;
    }

    private function makeWonder(string $name): Wonder
    {
        $wonder = new Wonder();
        $wonder->name = $name;

        return $wonder;
    }

    private function makeRoundWonder(string $name, ?Realm $realm): RoundWonder
    {
        $roundWonder = new RoundWonder();
        $roundWonder->setRelation('wonder', $this->makeWonder($name));
        $roundWonder->setRelation('realm', $realm);

        return $roundWonder;
    }
}
