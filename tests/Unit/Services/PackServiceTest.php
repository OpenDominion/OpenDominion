<?php

namespace OpenDominion\Tests\Unit\Services;

use CoreDataSeeder;
use DB;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\PackService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use RuntimeException;

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

    /** @var Request */
    protected $request;

    /** @var PackService */
    protected $packService;

    protected function setUp()
    {
        parent::setUp();

        $this->seed(CoreDataSeeder::class);

        $this->round = $this->createRound();
        $this->goodRace = Race::where('alignment', 'good')->firstOrFail();
        $this->evilRace = Race::where('alignment', 'evil')->firstOrFail();
        $this->goodRealm = $this->createRealm($this->round, $this->goodRace->alignment);
        $this->request = new Request();
        $this->createAndImpersonateUser();

        $this->packService = $this->app->make(PackService::class);
    }

    public function testGetOrCreatePackWhenCreatePackIsTrueReturnsNewPack()
    {
        // Arrange
        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name',
            'create_pack' => 'true',
            'pack_size' => 5
            ]);

        // Act
        $result = $this->packService->getOrCreatePack($this->request, $this->round, $this->goodRace);

        // Assert
        $this->assertEquals($result->id, 1);
    }

    public function testGetOrCreatePackWhenCreatePackIsTrueAndPackSizeIsLowerThan2Throws()
    {
        // Arrange
        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name',
            'create_pack' => 'true',
            'pack_size' => 1
            ]);

        $thrown = false;
        // Act
        try
        {
            $result = $this->packService->getOrCreatePack($this->request, $this->round, $this->goodRace);
        }
        catch(RuntimeException $e)
        {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }

    public function testGetOrCreatePackWhenCreatePackIsTrueAndPackSizeIsGreaterThan6Throws()
    {
        // Arrange
        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name',
            'create_pack' => 'true',
            'pack_size' => 7
            ]);

        $thrown = false;

        // Act
        try
        {
            $result = $this->packService->getOrCreatePack($this->request, $this->round, $this->goodRace);
        }
        catch(RuntimeException $e)
        {
            $thrown = true;
        }

        // Assert
        $this->assertTrue($thrown);
    }

    public function testGetOrCreatePackWhenCreatePackIsFalseAndExistingPackReturnsExistingPack()
    {
        // Arrange
        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name',
            'create_pack' => 'true',
            'pack_size' => 5
            ]);

        $existingPack = $this->packService->getOrCreatePack($this->request, $this->round, $this->goodRace);
        $existingPack->update(['realm_id' => $this->goodRealm->id]);
        $existingPack->load('realm');

        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name'
            ]);

        // Act
        $result = $this->packService->getOrCreatePack($this->request, $this->round, $this->goodRace);

        // Assert
        $this->assertEquals($result->id, $existingPack->id);
    }

    public function testGetOrCreatePackWhenCreatePackIsFalseAndExistingPackIsFullThrows()
    {
        // Arrange
        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name',
            'create_pack' => 'true',
            'pack_size' => 5
            ]);

        $existingPack = $this->packService->getOrCreatePack($this->request, $this->round, $this->goodRace);
        $existingPack->update(['size' => 0]);

        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name'
            ]);

        $thrown = false;

        // Act
        try
        {
            $result = $this->packService->getOrCreatePack($this->request, $this->round, $this->goodRace);
        }
        catch(RuntimeException $e)
        {
            $thrown = true;
        }

        // Assert
        $this->assertTrue($thrown);
    }

    public function testGetOrCreatePackWhenCreatePackIsFalseAndExistingPackIsWrongAlignmentThrows()
    {
        // Arrange
        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name',
            'create_pack' => 'true',
            'pack_size' => 5
            ]);

        $existingPack = $this->packService->getOrCreatePack($this->request, $this->round, $this->goodRace);
        $existingPack->update(['realm_id' => $this->goodRealm->id]);
        $existingPack->load('realm');

        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name'
            ]);

        $thrown = false;

        // Act
        try
        {
            $result = $this->packService->getOrCreatePack($this->request, $this->round, $this->evilRace);
        }
        catch(RuntimeException $e)
        {
            $thrown = true;
        }

        // Assert
        $this->assertTrue($thrown);
    }

    public function testGetOrCreatePackWhenCreatePackIsFalseAndNoExistingPackThrows()
    {
        // Arrange
        $this->request->replace([
            'pack_password' => 'password',
            'pack_name' => 'name'
            ]);

        $thrown = false;

        // Act
        try
        {
            $result = $this->packService->getOrCreatePack($this->request, $this->round, $this->goodRace);
        }
        catch(RuntimeException $e)
        {
            $thrown = true;
        }

        // Assert
        $this->assertTrue($thrown);
    }
}