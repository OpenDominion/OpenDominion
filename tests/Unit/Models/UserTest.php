<?php

namespace OpenDominion\Tests\Unit\Models;

use OpenDominion\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUnratedUserResolvesToNeutralRatingProfile(): void
    {
        $user = new User();
        $user->rating = 0;
        $user->affinities = null;

        $this->assertSame(User::DEFAULT_RATING, $user->getEffectiveRating());
        $this->assertSame(User::DEFAULT_AFFINITIES, $user->getEffectiveAffinities());
        $this->assertSame(User::DEFAULT_AFFINITIES['attacker'], $user->getAffinity('attacker'));
        $this->assertSame(User::DEFAULT_AFFINITIES['converter'], $user->getAffinity('converter'));
        $this->assertSame(User::DEFAULT_AFFINITIES['explorer'], $user->getAffinity('explorer'));
        $this->assertSame(User::DEFAULT_AFFINITIES['ops'], $user->getAffinity('ops'));
    }

    public function testLegacyZeroAffinitiesResolveToNeutralProfile(): void
    {
        $user = new User();
        $user->affinities = [
            'attacker' => 0,
            'converter' => 0,
            'explorer' => 0,
            'ops' => 0,
        ];

        $this->assertSame(User::DEFAULT_AFFINITIES, $user->getEffectiveAffinities());
    }

    public function testCalculatedRatingProfileOverridesNeutralDefaults(): void
    {
        $user = new User();
        $user->rating = 1725;
        $user->affinities = [
            'attacker' => 80,
            'converter' => 40,
            'explorer' => 20,
            'ops' => 10,
        ];

        $this->assertSame(1725.0, $user->getEffectiveRating());
        $this->assertSame([
            'attacker' => 80.0,
            'converter' => 40.0,
            'explorer' => 20.0,
            'ops' => 10.0,
        ], $user->getEffectiveAffinities());
        $this->assertSame(80.0, $user->getAffinity('attacker'));
        $this->assertSame(40.0, $user->getAffinity('converter'));
        $this->assertSame(20.0, $user->getAffinity('explorer'));
        $this->assertSame(10.0, $user->getAffinity('ops'));
    }
}
