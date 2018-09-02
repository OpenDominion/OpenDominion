<?php

namespace OpenDominion\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\PackService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use RuntimeException;

// todo: refactor manually test thrown exceptions to @expectedException annotation

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
        $this->createAndImpersonateUser();

        $this->packService = $this->app->make(PackService::class);
    }

    public function testGetOrCreatePackWhenCreatePackIsTrueReturnsNewPack()
    {
        // Act
        $result = $this->packService->getOrCreatePack(
            $this->round,
            $this->goodRace,
            'name',
            'password',
            5,
            true);

        // Assert
        $this->assertEquals($result->id, 1);
    }

    public function testGetOrCreatePackWhenCreatePackIsTrueAndPackSizeIsLowerThan2Throws()
    {
        // Arrange
        $thrown = false;

        // Act
        try {
            $result = $this->packService->getOrCreatePack(
                $this->round,
                $this->goodRace,
                'name',
                'password',
                1,
                true);
        } catch (RuntimeException $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }

    public function testGetOrCreatePackWhenCreatePackIsTrueAndPackSizeIsGreaterThan6Throws()
    {
        // Arrange
        $thrown = false;

        // Act
        try {
            $result = $this->packService->getOrCreatePack(
                $this->round,
                $this->goodRace,
                'name',
                'password',
                7,
                true);
        } catch (RuntimeException $e) {
            $thrown = true;
        }

        // Assert
        $this->assertTrue($thrown);
    }

    public function testGetOrCreatePackWhenCreatePackIsFalseAndExistingPackReturnsExistingPack()
    {
        // Arrange
        $existingPack = $this->packService->getOrCreatePack(
            $this->round,
            $this->goodRace,
            'name',
            'password',
            5,
            true);

        $existingPack->update(['realm_id' => $this->goodRealm->id]);
        $existingPack->load('realm');

        // Act
        $result = $this->packService->getOrCreatePack(
            $this->round,
            $this->goodRace,
            'name',
            'password',
            0,
            false);

        // Assert
        $this->assertEquals($result->id, $existingPack->id);
    }

    public function testGetOrCreatePackWhenCreatePackIsFalseAndExistingPackIsFullThrows()
    {
        // Arrange
        $existingPack = $this->packService->getOrCreatePack(
            $this->round,
            $this->goodRace,
            'name',
            'password',
            5,
            true);

        $existingPack->update(['size' => 0]);

        $thrown = false;

        // Act
        try {
            $result = $this->packService->getOrCreatePack(
                $this->round,
                $this->goodRace,
                'name',
                'password',
                0,
                false);

        } catch (RuntimeException $e) {
            $thrown = true;
        }

        // Assert
        $this->assertTrue($thrown);
    }

    public function testGetOrCreatePackWhenCreatePackIsFalseAndExistingPackIsWrongAlignmentThrows()
    {
        // Arrange
        $existingPack = $this->packService->getOrCreatePack(
            $this->round,
            $this->goodRace,
            'name',
            'password',
            5,
            true);

        $existingPack->update(['realm_id' => $this->goodRealm->id]);
        $existingPack->load('realm');

        $thrown = false;

        // Act
        try {
            $result = $this->packService->getOrCreatePack(
                $this->round,
                $this->evilRace,
                'name',
                'password',
                0,
                false);
        } catch (RuntimeException $e) {
            $thrown = true;
        }

        // Assert
        $this->assertTrue($thrown);
    }

    public function testGetOrCreatePackWhenCreatePackIsFalseAndNoExistingPackReturnsNull()
    {
        // Act
        $result = $this->packService->getOrCreatePack(
            $this->round,
            $this->goodRace,
            'name',
            'password',
            0,
            false);

        // Assert
        $this->assertNull($result);
    }
}
