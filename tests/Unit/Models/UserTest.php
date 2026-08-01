<?php

namespace OpenDominion\Tests\Unit\Models;

use OpenDominion\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUnratedUserResolvesToDefaultRating(): void
    {
        $user = new User();
        $user->rating = 0;

        $this->assertSame(User::DEFAULT_RATING, $user->getEffectiveRating());
    }

    public function testRatedUserKeepsStoredRating(): void
    {
        $user = new User();
        $user->rating = 1725;

        $this->assertSame(1725.0, $user->getEffectiveRating());
    }

    public function testMissingAffinitiesAreUnknown(): void
    {
        $user = new User();
        $user->affinities = null;

        $this->assertFalse($user->hasKnownAffinities());
    }

    public function testZeroAffinitySentinelIsUnknown(): void
    {
        $user = new User();
        $user->affinities = [
            'attacker' => 0,
            'converter' => 0,
            'explorer' => 0,
            'ops' => 0,
        ];

        $this->assertFalse($user->hasKnownAffinities());
    }

    public function testPartialAffinitiesAreUnknown(): void
    {
        $user = new User();
        $user->affinities = [
            'attacker' => 80,
            'converter' => 20,
        ];

        $this->assertFalse($user->hasKnownAffinities());
    }

    public function testCompleteAffinitiesAreKnown(): void
    {
        $user = new User();
        $user->affinities = [
            'attacker' => 80,
            'converter' => 20,
            'explorer' => 0,
            'ops' => 0,
        ];

        $this->assertTrue($user->hasKnownAffinities());
    }
}
