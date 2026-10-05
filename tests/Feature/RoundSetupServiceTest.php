<?php

namespace OpenDominion\Tests\Feature;

use OpenDominion\Models\Round;
use OpenDominion\Models\RoundSetupRun;
use OpenDominion\Services\RoundSetupService;
use OpenDominion\Tests\AbstractTestCase;
use RuntimeException;

class RoundSetupServiceTest extends AbstractTestCase
{
    public function testCompletedSetupIsNotRepeated(): void
    {
        $round = $this->createRound('+2 days');
        $service = app(RoundSetupService::class);
        $calls = 0;
        $callback = function (Round $lockedRound) use (&$calls): void {
            $calls++;
            $lockedRound->update(['assignment_complete' => true]);
        };

        $this->assertTrue($service->run($round, RoundSetupService::REALM_ASSIGNMENT, $callback));
        $this->assertFalse($service->run($round, RoundSetupService::REALM_ASSIGNMENT, $callback));

        $this->assertSame(1, $calls);
        $this->assertTrue((bool) $round->fresh()->assignment_complete);
        $this->assertSame(1, RoundSetupRun::query()->where('round_id', $round->id)->count());
    }

    public function testFailureRollsBackAndAllowsRetry(): void
    {
        $round = $this->createRound('+2 days');
        $originalName = $round->name;
        $service = app(RoundSetupService::class);

        try {
            $service->run($round, RoundSetupService::NON_PLAYER_GENERATION, function (Round $lockedRound): void {
                $lockedRound->update(['name' => 'Uncommitted setup']);
                throw new RuntimeException('Injected setup failure');
            });
            $this->fail('Expected setup failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected setup failure', $exception->getMessage());
        }

        $this->assertSame($originalName, $round->fresh()->name);
        $this->assertFalse(RoundSetupRun::query()->where('round_id', $round->id)->exists());
        $this->assertTrue($service->run($round, RoundSetupService::NON_PLAYER_GENERATION, function (Round $lockedRound): void {
            $lockedRound->update(['name' => 'Completed setup']);
        }));
        $this->assertSame('Completed setup', $round->fresh()->name);
        $this->assertSame(1, RoundSetupRun::query()->where('round_id', $round->id)->count());
    }

    public function testSetupReceivesFreshRoundWithUncachedRelations(): void
    {
        $round = $this->createRound('+2 days');
        $round->load('realms');
        $this->createRealm($round);
        Round::query()->whereKey($round->id)->update(['name' => 'Current round']);

        app(RoundSetupService::class)->run($round, RoundSetupService::REALM_ASSIGNMENT, function (Round $lockedRound) use ($round): void {
            $this->assertNotSame($round, $lockedRound);
            $this->assertSame('Current round', $lockedRound->name);
            $this->assertCount(1, $lockedRound->realms);
            $this->assertGreaterThan(1, $lockedRound->getConnection()->transactionLevel());
        });
    }

    public function testOperationsAndRoundsHaveIndependentReceipts(): void
    {
        $firstRound = $this->createRound('+2 days');
        $secondRound = $this->createRound('+3 days');
        $service = app(RoundSetupService::class);
        $callback = static function (Round $round): void {
        };

        $this->assertTrue($service->run($firstRound, RoundSetupService::REALM_ASSIGNMENT, $callback));
        $this->assertTrue($service->run($firstRound, RoundSetupService::NON_PLAYER_GENERATION, $callback));
        $this->assertTrue($service->run($secondRound, RoundSetupService::REALM_ASSIGNMENT, $callback));
        $this->assertSame(2, RoundSetupRun::query()->where('round_id', $firstRound->id)->count());
        $this->assertSame(1, RoundSetupRun::query()->where('round_id', $secondRound->id)->count());
    }
}
