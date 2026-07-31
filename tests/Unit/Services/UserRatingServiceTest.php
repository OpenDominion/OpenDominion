<?php

namespace OpenDominion\Tests\Unit\Services;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\User;
use OpenDominion\Services\UserRatingService;
use PHPUnit\Framework\TestCase;

class UserRatingServiceTest extends TestCase
{
    private UserRatingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserRatingService($this->createStub(LandCalculator::class));
    }

    public function testNoHistoryUsesNeutralRatingProfile(): void
    {
        $this->assertSame(User::DEFAULT_RATING, $this->service->averageBestFinishes([]));
        $this->assertSame(User::DEFAULT_AFFINITIES, $this->service->calculateAffinities([]));
    }
}
