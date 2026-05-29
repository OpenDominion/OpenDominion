<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class TrainActionTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testTrainingPlanewalkerElementalIsRejected()
    {
        $user = $this->createUser();
        $round = $this->createRound('-7 days');
        $race = Race::where('key', 'planewalker')->firstOrFail();
        $dominion = $this->createDominionWithLegacyStats($user, $round, $race);
        $trainActionService = app(TrainActionService::class);

        $dominion->update([
            'resource_platinum' => 100000,
            'military_draftees' => 100,
        ]);

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('cannot be trained directly');

        $trainActionService->train($dominion, ['military_unit1' => 1]);
    }

    public function testTrainingPlanewalkerSummonerIsAllowed()
    {
        $user = $this->createUser();
        $round = $this->createRound('-7 days');
        $race = Race::where('key', 'planewalker')->firstOrFail();
        $dominion = $this->createDominionWithLegacyStats($user, $round, $race);
        $trainActionService = app(TrainActionService::class);

        $dominion->update([
            'resource_platinum' => 100000,
            'military_draftees' => 100,
        ]);

        $result = $trainActionService->train($dominion, ['military_unit4' => 1]);

        $this->assertArrayHasKey('totalCosts', $result['data']);
        $this->assertGreaterThan(0, $result['data']['totalCosts']['platinum']);
        $this->assertEquals(1, $result['data']['totalCosts']['draftees']);
    }
}
