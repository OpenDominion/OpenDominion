<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\Spell;
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

    public function testTrainingIcekinArchmagesWithoutArcaneInfusionCostsWizards()
    {
        $dominion = $this->createIcekinDominion();
        $trainingCalculator = app(TrainingCalculator::class);

        $costs = $trainingCalculator->getTrainingCostsPerUnit($dominion)['archmages'];

        $this->assertEquals(1000, $costs['platinum']);
        $this->assertEquals(1, $costs['wizards']);
        $this->assertArrayNotHasKey('draftees', $costs);

        $result = app(TrainActionService::class)->train($dominion, ['military_archmages' => 2]);

        $this->assertEquals(2000, $result['data']['totalCosts']['platinum']);
        $this->assertEquals(2, $result['data']['totalCosts']['wizards']);
        $this->assertEquals(0, $result['data']['totalCosts']['draftees']);
    }

    public function testTrainingIcekinArchmagesWithArcaneInfusionCostsDraftees()
    {
        $dominion = $this->createIcekinDominion();
        $this->castArcaneInfusion($dominion);
        $trainingCalculator = app(TrainingCalculator::class);

        $costs = $trainingCalculator->getTrainingCostsPerUnit($dominion)['archmages'];

        $this->assertEquals(1400, $costs['platinum']);
        $this->assertEquals(1, $costs['draftees']);
        $this->assertArrayNotHasKey('wizards', $costs);

        $result = app(TrainActionService::class)->train($dominion, ['military_archmages' => 2]);

        $this->assertEquals(2700, $result['data']['totalCosts']['platinum']);
        $this->assertEquals(2, $result['data']['totalCosts']['draftees']);
        $this->assertEquals(0, $result['data']['totalCosts']['wizards']);
    }

    public function testTrainingIcekinArchmagesWithArcaneInfusionIsLimitedByDraftees()
    {
        $dominion = $this->createIcekinDominion();
        $this->castArcaneInfusion($dominion);

        $dominion->update([
            'resource_platinum' => 1000000,
            'military_draftees' => 3,
            'military_wizards' => 500,
        ]);

        $this->assertEquals(3, app(TrainingCalculator::class)->getMaxTrainable($dominion)['archmages']);

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('lack of draftees');

        app(TrainActionService::class)->train($dominion, ['military_archmages' => 4]);
    }

    protected function createIcekinDominion(): Dominion
    {
        $user = $this->createUser();
        $round = $this->createRound('-7 days');
        $race = Race::where('key', 'icekin')->firstOrFail();
        $dominion = $this->createDominionWithLegacyStats($user, $round, $race);

        $dominion->update([
            'resource_platinum' => 100000,
            'military_draftees' => 100,
            'military_wizards' => 25,
        ]);

        return $dominion;
    }

    protected function castArcaneInfusion(Dominion $dominion): void
    {
        DominionSpell::create([
            'dominion_id' => $dominion->id,
            'spell_id' => Spell::where('key', 'arcane_infusion')->firstOrFail()->id,
            'duration' => 12,
        ]);

        $dominion->refresh();
    }
}
