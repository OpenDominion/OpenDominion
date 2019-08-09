<?php

namespace OpenDominion\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\PackService;
use OpenDominion\Services\RealmFinderService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class PackServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

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

    protected function setUp()
    {
        parent::setUp();

        $this->seedDatabase();

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
        $this->assertEquals(1, $pack->id);
    }

    /**
     * @expectedException \OpenDominion\Exceptions\GameException
     */
    public function testCreatePackWhenPackSizeIsLowerThan2Throws()
    {
        // Act
        $this->packService->createPack(
            $this->goodDominion,
            'pack name',
            'pack password',
            1
        );
    }

    /**
     * @expectedException \OpenDominion\Exceptions\GameException
     */
    public function testCreatePackWhenPackSizeIsGreaterThanRoundPackSizeThrows()
    {
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
     *
     * @expectedException \Illuminate\Database\QueryException
     */
    public function testCreatePackWhenPackWithSameNameAndPasswordAlreadyExistsThrows()
    {
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
        app(RealmFinderService::class)->maxPacksPerRealm = null;

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

    /**
     * @expectedException \OpenDominion\Exceptions\GameException
     */
    public function testGetPackWhenNoPackExistsThrows()
    {
        // Act
        $pack = $this->packService->getPack(
            $this->round,
            'pack name',
            'pack password',
            $this->goodRace
        );
    }

    /**
     * @expectedException \OpenDominion\Exceptions\GameException
     */
    public function testGetPackWhenPackIsFullThrows()
    {
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

    /**
     * @expectedException \OpenDominion\Exceptions\GameException
     */
    public function testGetPackWhenRaceAlignmentMismatchThrows()
    {
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
