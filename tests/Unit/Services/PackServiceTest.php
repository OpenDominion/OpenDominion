<?php

namespace OpenDominion\Tests\Unit\Services;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\PackService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class PackServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var Round */
    protected $round;

    /** @var Race */
    protected $goodRace;

    /** @var Race */
    protected $evilRace;

    /** @var Realm */
    protected $goodRealm;

    /** @var Dominion */
    protected $goodDominion;

    /** @var PackService */
    protected $packService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = $this->createRound();
        $this->goodRace = Race::where('alignment', 'good')->firstOrFail();
        $this->evilRace = Race::where('alignment', 'evil')->firstOrFail();
        $this->goodRealm = $this->createRealm($this->round, $this->goodRace->alignment);
        $user = $this->createAndImpersonateUser();
        $this->goodDominion = $this->createDominion($user, $this->round, $this->goodRace);

        $this->packService = $this->app->make(PackService::class);
    }

    public function testCreatePackReturnsNewPack()
    {
        // Act
        $pack = $this->packService->createPack(
            $this->goodDominion,
            'pack name',
            'pack password',
            3
        );

        // Assert
        $this->assertTrue($pack->exists);
    }

    public function testCreatePackWhenPackSizeIsLowerThan2Throws()
    {
        $this->expectException(GameException::class);

        // Act
        $this->packService->createPack(
            $this->goodDominion,
            'pack name',
            'pack password',
            1
        );
    }

    public function testCreatePackWhenPackSizeIsGreaterThanRoundPackSizeThrows()
    {
        $this->expectException(GameException::class);

        // Act
        $this->packService->createPack(
            $this->goodDominion,
            'pack name',
            'pack password',
            $this->round->pack_size + 1
        );
    }

    /**
     * Currently limited by UNIQUE-constraint in the database.
     */
    public function testCreatePackWhenPackWithSameNameAndPasswordAlreadyExistsThrows()
    {
        $this->expectException(QueryException::class);

        // Act
        for ($i = 0; $i < 2; $i++) {
            $this->packService->createPack(
                $this->createDominion($this->createUser(), $this->round, $this->goodRace),
                'pack name',
                'pack password',
                3
            );
        }
    }

    public function testCreatePackCreatesPackInARealmWithAnotherExistingPack()
    {
        // Arrange
        $pack1 = $this->packService->createPack(
            $this->createDominion($this->createUser(), $this->round, $this->goodRace),
            'pack name 1',
            'pack password',
            3
        );

        // Act
        $pack2 = $this->packService->createPack(
            $this->createDominion($this->createUser(), $this->round, $this->goodRace),
            'pack name 2',
            'pack password',
            3
        );

        // Assert
        $this->assertEquals($this->goodRealm->id, $pack1->realm_id);
        $this->assertEquals($this->goodRealm->id, $pack2->realm_id);
    }

    public function testGetPackWhenNoPackExistsThrows()
    {
        $this->expectException(GameException::class);

        // Act
        $pack = $this->packService->getPack(
            $this->round,
            'pack name',
            'pack password',
            $this->goodRace
        );
    }

    public function testGetPackWhenPackIsFullThrows()
    {
        $this->expectException(GameException::class);

        // Arrange
        $pack = $this->packService->createPack(
            $this->goodDominion,
            'pack name',
            'pack password',
            3
        );

        $this->goodDominion->pack_id = $pack->id;
        $this->goodDominion->save();

        for ($i = 0; $i < 2; $i++) {
            $dominion = $this->createDominion(
                $this->createUser(),
                $this->round,
                $this->goodRace
            );

            $dominion->pack_id = $pack->id;
            $dominion->save();
        }

        // Act
        $this->packService->getPack(
            $this->round,
            'pack name',
            'pack password',
            $this->goodRace
        );

        // Assert
        $this->assertEquals(3, $pack->dominions->count());
        $this->assertTrue($pack->isFull());
    }

    public function testGetPackWhenRaceAlignmentMismatchThrows()
    {
        $this->expectException(GameException::class);

        // Arrange
        $this->packService->createPack(
            $this->goodDominion,
            'pack name',
            'pack password',
            3
        );

        // Act
        $this->packService->getPack(
            $this->round,
            'pack name',
            'pack password',
            $this->evilRace
        );
    }
}
